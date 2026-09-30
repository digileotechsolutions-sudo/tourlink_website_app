<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use App\Role;

class TripPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Admin, Role::Operator], true);
    }

    public function view(User $user, Trip $trip): bool
    {
        return $user->role === Role::Admin
            || ($trip->status?->value === 'PUBLISHED' && $trip->verification_status?->value === 'APPROVED');
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Operator;
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->role === Role::Admin || ($user->role === Role::Operator && $trip->operator_id === $user->id);
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->role === Role::Admin || ($user->role === Role::Operator && $trip->operator_id === $user->id);
    }

    public function approve(User $user, Trip $trip): bool
    {
        return $user->role === Role::Admin;
    }
}
