<?php

namespace Database\Factories;

use App\BookingStatus;
use App\BookingType;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'TL-'.Str::upper(Str::random(12)),
            'type' => BookingType::Trip,
            'status' => BookingStatus::Pending,
            'traveler_id' => User::factory(),
            'trip_id' => Trip::factory(),
            'vehicle_id' => null,
            'start_date' => now()->addWeeks(2),
            'end_date' => now()->addWeeks(2)->addDays(3),
            'travelers' => 1,
            'pickup_location' => 'Westlands',
            'driver_required' => false,
            'base_amount' => 15000,
            'fees' => 0,
            'discount' => 0,
            'total_amount' => 15000,
            'currency' => 'KES',
            'notes' => null,
        ];
    }
}
