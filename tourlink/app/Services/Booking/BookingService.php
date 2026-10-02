<?php

namespace App\Services\Booking;

use App\BookingStatus;
use App\BookingType;
use App\ListingStatus;
use App\Models\Booking;
use App\Models\Commission;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\PaymentStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BookingService
{
    public function createTripBooking(
        User $traveler,
        string $tripIdentifier,
        int $travelers,
        DateTimeInterface $startDate,
        ?string $pickupLocation = null,
    ): Booking {
        if ($travelers < 1 || $travelers > 100) {
            throw new InvalidArgumentException('Traveler count is outside the supported range.');
        }

        return DB::transaction(function () use ($traveler, $tripIdentifier, $travelers, $startDate, $pickupLocation): Booking {
            $trip = Trip::query()
                ->where(fn (Builder $query): Builder => $query->whereKey($tripIdentifier)->orWhere('slug', $tripIdentifier))
                ->lockForUpdate()
                ->first();

            if (! $trip || $trip->status !== ListingStatus::Published) {
                throw new RuntimeException('TRIP_UNAVAILABLE');
            }

            $start = CarbonImmutable::instance($startDate)->utc();
            $availability = $trip->availabilities()
                ->whereBetween('date', [$start->startOfDay(), $start->endOfDay()])
                ->lockForUpdate()
                ->first();

            if ($availability && $availability->seats < $travelers) {
                throw new RuntimeException('NOT_ENOUGH_SEATS');
            }

            if (! $availability && $trip->available_seats < $travelers) {
                throw new RuntimeException('NOT_ENOUGH_SEATS');
            }

            if ($availability) {
                $availability->decrement('seats', $travelers);
            } else {
                $trip->decrement('available_seats', $travelers);
            }
            $total = $trip->price_per_person * $travelers;
            $booking = Booking::query()->create([
                'reference' => $this->bookingReference(),
                'type' => BookingType::Trip,
                'status' => BookingStatus::Pending,
                'traveler_id' => $traveler->id,
                'trip_id' => $trip->id,
                'start_date' => $start,
                'end_date' => $trip->return_date,
                'travelers' => $travelers,
                'pickup_location' => $pickupLocation,
                'base_amount' => $total,
                'fees' => 0,
                'discount' => 0,
                'total_amount' => $total,
                'currency' => 'KES',
            ]);

            $this->createPaymentAndCommission($booking, $total);
            Notification::query()->create([
                'user_id' => $trip->operator_id,
                'title' => 'New trip booking',
                'body' => $booking->reference.' is waiting for your confirmation.',
                'type' => 'BOOKING',
            ]);

            return $booking;
        }, attempts: 3);
    }

    public function createVehicleBooking(
        User $traveler,
        string $vehicleIdentifier,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        bool $driverRequired,
        ?string $pickupLocation = null,
    ): Booking {
        $start = CarbonImmutable::instance($startDate)->utc();
        $end = CarbonImmutable::instance($endDate)->utc();

        if ($end <= $start) {
            throw new RuntimeException('INVALID_DATES');
        }

        return DB::transaction(function () use ($traveler, $vehicleIdentifier, $start, $end, $driverRequired, $pickupLocation): Booking {
            $vehicle = Vehicle::query()
                ->where(fn (Builder $query): Builder => $query->whereKey($vehicleIdentifier)->orWhere('slug', $vehicleIdentifier))
                ->lockForUpdate()
                ->first();

            if (! $vehicle || $vehicle->status !== ListingStatus::Published) {
                throw new RuntimeException('VEHICLE_UNAVAILABLE');
            }

            $lastAvailabilityDate = $end->isSameDay($start)
                ? $start->endOfDay()
                : $end->startOfDay()->subSecond();
            $unavailable = $vehicle->availabilities()
                ->whereBetween('date', [$start->startOfDay(), $lastAvailabilityDate])
                ->where('available', false)
                ->lockForUpdate()
                ->exists();

            if ($unavailable) {
                throw new RuntimeException('VEHICLE_UNAVAILABLE');
            }

            $overlap = Booking::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::Refunded->value])
                ->where('start_date', '<', $end)
                ->where('end_date', '>', $start)
                ->exists();

            if ($overlap) {
                throw new RuntimeException('VEHICLE_BOOKED');
            }

            $days = max(1, (int) ceil(($end->getTimestamp() - $start->getTimestamp()) / 86400));
            $total = $vehicle->price_per_day * $days;
            $booking = Booking::query()->create([
                'reference' => $this->bookingReference(),
                'type' => BookingType::Vehicle,
                'status' => BookingStatus::Pending,
                'traveler_id' => $traveler->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $start,
                'end_date' => $end,
                'travelers' => 1,
                'pickup_location' => $pickupLocation,
                'driver_required' => $driverRequired,
                'base_amount' => $total,
                'fees' => 0,
                'discount' => 0,
                'total_amount' => $total,
                'currency' => 'KES',
            ]);

            $this->createPaymentAndCommission($booking, $total);
            Notification::query()->create([
                'user_id' => $vehicle->owner_id,
                'title' => 'New vehicle request',
                'body' => $booking->reference.' is waiting for your response.',
                'type' => 'VEHICLE_BOOKING',
            ]);

            return $booking;
        }, attempts: 3);
    }

    public function cancel(User $traveler, string $bookingId, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($traveler, $bookingId, $reason): Booking {
            $booking = Booking::query()->whereKey($bookingId)->lockForUpdate()->first();

            if (! $booking || $booking->traveler_id !== $traveler->id) {
                throw new RuntimeException('NOT_FOUND');
            }

            if (in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Completed, BookingStatus::Refunded], true)) {
                throw new RuntimeException('NOT_CANCELLABLE');
            }

            if (in_array($booking->status, [BookingStatus::Paid, BookingStatus::InProgress], true)) {
                throw new RuntimeException('PAYMENT_REQUIRES_REFUND');
            }

            return $this->cancelLocked($booking, $reason, false);
        }, attempts: 3);
    }

    public function cancelByAdmin(User $admin, string $bookingId, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($bookingId, $reason): Booking {
            $booking = Booking::query()->whereKey($bookingId)->lockForUpdate()->first();

            if (! $booking) {
                throw new RuntimeException('NOT_FOUND');
            }

            if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
                throw new RuntimeException('BOOKING_STATE_REQUIRES_FINANCE_REVIEW');
            }

            if ($booking->payments()->whereIn('status', [
                PaymentStatus::Successful->value,
                PaymentStatus::Refunded->value,
                PaymentStatus::PartiallyRefunded->value,
            ])->exists()) {
                throw new RuntimeException('PAYMENT_REQUIRES_REFUND');
            }

            return $this->cancelLocked($booking, $reason, true);
        }, attempts: 3);
    }

    private function cancelLocked(Booking $booking, ?string $reason, bool $adminInitiated): Booking
    {
        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'notes' => $reason,
        ])->save();

        $providerId = null;
        if ($booking->trip_id) {
            $trip = Trip::query()->whereKey($booking->trip_id)->lockForUpdate()->first();
            if ($trip) {
                $availability = $trip->availabilities()
                    ->whereBetween('date', [$booking->start_date->startOfDay(), $booking->start_date->endOfDay()])
                    ->lockForUpdate()
                    ->first();

                if ($availability) {
                    $availability->increment('seats', $booking->travelers);
                } else {
                    $trip->increment('available_seats', $booking->travelers);
                }
                $providerId = $trip->operator_id;
            }
        } elseif ($booking->vehicle_id) {
            $providerId = Vehicle::query()->whereKey($booking->vehicle_id)->value('owner_id');
        }

        if ($providerId) {
            Notification::query()->create([
                'user_id' => $providerId,
                'title' => 'Booking cancelled',
                'body' => $booking->reference.($adminInitiated ? ' was cancelled by Havenedge Tourlink support.' : ' was cancelled by the traveler.'),
                'type' => 'BOOKING_CANCELLED',
            ]);
        }

        if ($adminInitiated) {
            Notification::query()->create([
                'user_id' => $booking->traveler_id,
                'title' => 'Booking cancelled',
                'body' => $booking->reference.' was cancelled by Havenedge Tourlink support.'.($reason ? ' '.$reason : ''),
                'type' => 'BOOKING_CANCELLED',
            ]);
        }

        return $booking->refresh();
    }

    private function createPaymentAndCommission(Booking $booking, int $total): void
    {
        Payment::query()->create([
            'booking_id' => $booking->id,
            'merchant_reference' => $booking->reference,
            'amount' => $total,
        ]);

        $commission = (int) round($total * 0.1);
        Commission::query()->create([
            'booking_id' => $booking->id,
            'rate' => 0.1,
            'gross_amount' => $total,
            'commission_amount' => $commission,
            'provider_amount' => $total - $commission,
        ]);
    }

    private function bookingReference(): string
    {
        return 'TL-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
    }
}
