<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use App\Role;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Admin, Role::VehicleOwner], true);
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->role === Role::Admin
            || ($vehicle->status?->value === 'PUBLISHED' && $vehicle->verification_status?->value === 'APPROVED');
    }

    public function create(User $user): bool
    {
        return $user->role === Role::VehicleOwner;
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->role === Role::Admin || ($user->role === Role::VehicleOwner && $vehicle->owner_id === $user->id);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->role === Role::Admin || ($user->role === Role::VehicleOwner && $vehicle->owner_id === $user->id);
    }

    public function approve(User $user, Vehicle $vehicle): bool
    {
        return $user->role === Role::Admin;
    }
}
