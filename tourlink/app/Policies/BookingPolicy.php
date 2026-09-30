<?php

namespace App\Policies;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Role;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Admin, Role::Traveler, Role::Operator, Role::VehicleOwner], true);
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->role === Role::Admin
            || $booking->traveler_id === $user->id
            || ($user->role === Role::Operator && $booking->trip?->operator_id === $user->id)
            || ($user->role === Role::VehicleOwner && $booking->vehicle?->owner_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Traveler;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->role === Role::Admin
            || ($user->role === Role::Operator && $booking->trip?->operator_id === $user->id)
            || ($user->role === Role::VehicleOwner && $booking->vehicle?->owner_id === $user->id);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->role === Role::Traveler
            && $booking->traveler_id === $user->id
            && in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true);
    }
}
