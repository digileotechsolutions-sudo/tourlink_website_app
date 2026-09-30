<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->value === 'TRAVELER';
    }

    public function rules(): array
    {
        return [
            'trip' => ['required', 'string', 'exists:trips,id'],
            'travelers' => ['required', 'integer', 'min:1', 'max:100'],
            'start_date' => ['required', 'date'],
            'pickup_location' => ['nullable', 'string', 'max:120'],
        ];
    }
}
