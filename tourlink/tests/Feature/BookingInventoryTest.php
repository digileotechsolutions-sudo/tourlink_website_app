<?php

namespace Tests\Feature;

use App\BookingStatus;
use App\BookingType;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\TripAvailability;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Booking\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BookingInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_booking_reserves_seats_without_creating_an_unpaid_commission(): void
    {
        $trip = Trip::factory()->create(['available_seats' => 12]);
        $departure = $trip->departure_date->startOfDay();
        $availability = TripAvailability::query()->create(['trip_id' => $trip->id, 'date' => $departure, 'seats' => 2]);

        $booking = app(BookingService::class)->createTripBooking(
            User::factory()->create(),
            $trip->id,
            2,
            $departure,
        );

        $this->assertSame(BookingType::Trip, $booking->type);
        $this->assertSame(2, $booking->travelers);
        $this->assertSame(0, $availability->refresh()->seats);
        $this->assertDatabaseMissing('commissions', ['booking_id' => $booking->id]);
    }

    public function test_trip_booking_rejects_more_seats_than_remain_for_that_departure(): void
    {
        $trip = Trip::factory()->create();
        $departure = $trip->departure_date->startOfDay();
        TripAvailability::query()->create(['trip_id' => $trip->id, 'date' => $departure, 'seats' => 1]);

        try {
            app(BookingService::class)->createTripBooking(User::factory()->create(), $trip->id, 2, $departure);
            $this->fail('A booking above the remaining seat inventory should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('NOT_ENOUGH_SEATS', $exception->getMessage());
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_operator_cannot_reset_trip_inventory_while_a_booking_reserves_seats(): void
    {
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $trip = Trip::factory()->for($operator, 'operator')->create();
        $departure = $trip->departure_date->startOfDay();
        $availability = TripAvailability::query()->create(['trip_id' => $trip->id, 'date' => $departure, 'seats' => 2]);
        app(BookingService::class)->createTripBooking(User::factory()->create(), $trip->id, 1, $departure);

        $this->actingAs($operator)
            ->put(route('operator.trips.availability', $trip), [
                'availability' => [['date' => $departure->toDateString(), 'seats' => 20]],
            ])
            ->assertSessionHasErrors('availability');

        $this->assertSame(1, $availability->refresh()->seats);
    }

    public function test_cancelling_a_provider_trip_booking_restores_reserved_seats(): void
    {
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $trip = Trip::factory()->for($operator, 'operator')->create();
        $departure = $trip->departure_date->startOfDay();
        $availability = TripAvailability::query()->create(['trip_id' => $trip->id, 'date' => $departure, 'seats' => 3]);
        $booking = app(BookingService::class)->createTripBooking(User::factory()->create(), $trip->id, 2, $departure);

        app(BookingService::class)->updateProviderStatus($operator, $booking->id, BookingStatus::Cancelled);

        $this->assertSame(BookingStatus::Cancelled, $booking->refresh()->status);
        $this->assertSame(3, $availability->refresh()->seats);
    }

    public function test_vehicle_booking_reserves_one_whole_vehicle_and_rejects_an_overlapping_request(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner, 'owner')->create();
        $traveler = User::factory()->create();
        $start = CarbonImmutable::now()->addWeeks(3)->startOfDay();
        $end = $start->addDays(2);
        $booking = app(BookingService::class)->createVehicleBooking($traveler, $vehicle->id, $start, $end, false);

        $this->assertSame(BookingType::Vehicle, $booking->type);
        $this->assertSame(1, $booking->travelers);

        try {
            app(BookingService::class)->createVehicleBooking(User::factory()->create(), $vehicle->id, $start->addDay(), $end->addDay(), false);
            $this->fail('An overlapping whole-vehicle booking should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('VEHICLE_BOOKED', $exception->getMessage());
        }

        $this->assertDatabaseCount('bookings', 1);
    }
}
