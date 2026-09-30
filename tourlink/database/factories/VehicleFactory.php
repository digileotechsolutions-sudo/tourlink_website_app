<?php

namespace Database\Factories;

use App\ListingStatus;
use App\Models\Destination;
use App\Models\User;
use App\Models\Vehicle;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fn (array $attributes): string => Str::slug($attributes['name']).'-'.Str::lower(Str::random(6)),
            'name' => fake()->words(3, true),
            'registration_number' => fake()->bothify('K?? ###X'),
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2022,
            'body_type' => 'SUV',
            'engine_cc' => 2800,
            'colour' => 'White',
            'seating_capacity' => 7,
            'tare_weight' => 2200,
            'axles' => 2,
            'load_capacity' => 700,
            'transmission' => 'Automatic',
            'fuel_type' => 'Diesel',
            'air_conditioning' => true,
            'four_by_four' => true,
            'driver_included' => true,
            'price_per_day' => 12000,
            'location' => 'Nairobi',
            'status' => ListingStatus::Published,
            'verification_status' => VerificationStatus::Approved,
            'owner_id' => User::factory(),
            'destination_id' => Destination::factory(),
        ];
    }
}
