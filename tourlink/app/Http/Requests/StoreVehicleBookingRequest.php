<?php

namespace App\Http\Requests;

use App\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [Role::Traveler, Role::Operator, Role::VehicleOwner], true);
    }

    public function rules(): array
    {
        return [
            'vehicle' => ['required', 'string', 'exists:vehicles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'driver_required' => ['sometimes', 'boolean'],
            'pickup_location' => ['nullable', 'string', 'max:120'],
        ];
    }
}
