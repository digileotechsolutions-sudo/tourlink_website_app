<?php

namespace App\Services\Admin;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\BookingStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Role;
use App\VerificationStatus;
use Illuminate\Support\Facades\DB;

class AdminDashboardMetrics
{
    public function build(): array
    {
        $paidBookings = Payment::query()
            ->select('booking_id')
            ->where('status', 'SUCCESSFUL')
            ->distinct();

        $payoutsByRole = Payout::query()
            ->join('users', 'users.id', '=', 'payouts.provider_id')
            ->select('users.role')
            ->selectRaw('SUM(payouts.amount) AS amount')
            ->groupBy('users.role')
            ->pluck('amount', 'role');

        $pendingVerification = User::query()->where('approval_status', AccountApprovalStatus::Pending)->count()
            + DB::table('verification_requests')->whereIn('status', [
                VerificationStatus::Pending->value,
                VerificationStatus::UnderReview->value,
                VerificationStatus::MoreInfo->value,
            ])->count()
            + Trip::query()->where('verification_status', VerificationStatus::Pending)->count()
            + Vehicle::query()->where('verification_status', VerificationStatus::Pending)->count();

        return [
            'statistics' => [
                'totalUsers' => User::query()->count(),
                'activeUsers' => User::query()->where('account_status', AccountStatus::Active)->count(),
                'tourOperators' => User::query()->where('role', Role::Operator)->count(),
                'vehicleOwners' => User::query()->where('role', Role::VehicleOwner)->count(),
                'totalTrips' => Trip::query()->count(),
                'totalVehicles' => Vehicle::query()->count(),
                'totalBookings' => Booking::query()->count(),
                'completedBookings' => Booking::query()->where('status', BookingStatus::Completed)->count(),
                'cancelledBookings' => Booking::query()->where('status', BookingStatus::Cancelled)->count(),
                'revenue' => Payment::query()->where('status', 'SUCCESSFUL')->sum('amount'),
                'tourlinkCommission' => DB::table('commissions')->whereIn('booking_id', $paidBookings)->sum('commission_amount'),
                'operatorPayouts' => (int) ($payoutsByRole[Role::Operator->value] ?? 0),
                'vehicleOwnerPayouts' => (int) ($payoutsByRole[Role::VehicleOwner->value] ?? 0),
                'pendingVerification' => $pendingVerification,
                'averageRating' => (float) (Review::query()->where('hidden_by_admin', false)->avg('rating') ?? 0),
            ],
            'bookingChart' => $this->monthlyChart(
                Booking::query()
                    ->selectRaw($this->monthBucketExpression('created_at').' AS bucket, COUNT(*) AS total')
                    ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
                    ->groupBy('bucket')
                    ->pluck('total', 'bucket'),
            ),
            'revenueChart' => $this->monthlyChart(
                Payment::query()
                    ->selectRaw($this->monthBucketExpression('COALESCE(paid_at, created_at)').' AS bucket, SUM(amount) AS total')
                    ->where('status', 'SUCCESSFUL')
                    ->whereRaw('COALESCE(paid_at, created_at) >= ?', [now()->startOfMonth()->subMonths(11)])
                    ->groupBy('bucket')
                    ->pluck('total', 'bucket'),
            ),
            'popularDestinations' => $this->popularDestinations(),
            'topTrips' => $this->topTrips(),
            'topVehicles' => $this->topVehicles(),
            'operatorPerformance' => $this->operatorPerformance(),
        ];
    }

    private function monthlyChart(mixed $data): array
    {
        $months = collect(range(11, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));

        return [
            'labels' => $months->map(fn ($month): string => $month->format('M'))->all(),
            'values' => $months->map(fn ($month): int => (int) ($data[$month->format('Y-m')] ?? 0))->all(),
        ];
    }

    private function monthBucketExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function popularDestinations(): array
    {
        $rows = Destination::query()
            ->join('trips', 'trips.destination_id', '=', 'destinations.id')
            ->join('bookings', 'bookings.trip_id', '=', 'trips.id')
            ->whereNotIn('bookings.status', [BookingStatus::Cancelled->value, BookingStatus::Refunded->value])
            ->select('destinations.name AS label')
            ->selectRaw('COUNT(bookings.id) AS total')
            ->groupBy('destinations.id', 'destinations.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return $this->rankedChart($rows);
    }

    private function topTrips(): array
    {
        $rows = Trip::query()
            ->join('bookings', 'bookings.trip_id', '=', 'trips.id')
            ->whereNotIn('bookings.status', [BookingStatus::Cancelled->value, BookingStatus::Refunded->value])
            ->select('trips.name AS label')
            ->selectRaw('COUNT(bookings.id) AS total')
            ->groupBy('trips.id', 'trips.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return $this->rankedChart($rows);
    }

    private function topVehicles(): array
    {
        $rows = Vehicle::query()
            ->join('bookings', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->whereNotIn('bookings.status', [BookingStatus::Cancelled->value, BookingStatus::Refunded->value])
            ->select('vehicles.name AS label')
            ->selectRaw('COUNT(bookings.id) AS total')
            ->groupBy('vehicles.id', 'vehicles.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return $this->rankedChart($rows);
    }

    private function operatorPerformance(): array
    {
        $rows = User::query()
            ->join('trips', 'trips.operator_id', '=', 'users.id')
            ->leftJoin('bookings', function ($join): void {
                $join->on('bookings.trip_id', '=', 'trips.id')
                    ->whereNotIn('bookings.status', [BookingStatus::Cancelled->value, BookingStatus::Refunded->value]);
            })
            ->where('users.role', Role::Operator->value)
            ->select('users.name AS label')
            ->selectRaw('COUNT(bookings.id) AS total')
            ->selectRaw('COALESCE(SUM(bookings.total_amount), 0) AS value')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($value): int => (int) $value)->all(),
            'amounts' => $rows->pluck('value')->map(fn ($value): int => (int) $value)->all(),
        ];
    }

    private function rankedChart(mixed $rows): array
    {
        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($value): int => (int) $value)->all(),
        ];
    }
}
