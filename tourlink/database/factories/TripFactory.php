<?php

namespace Database\Factories;

use App\ListingStatus;
use App\Models\Destination;
use App\Models\Trip;
use App\Models\TripCategory;
use App\Models\User;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
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
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'starting_point' => 'Nairobi',
            'ending_point' => fake()->city(),
            'duration_days' => 3,
            'departure_date' => now()->addWeeks(2),
            'return_date' => now()->addWeeks(2)->addDays(3),
            'price_per_person' => 15000,
            'max_travelers' => 12,
            'min_travelers' => 1,
            'available_seats' => 12,
            'pickup_points' => ['Westlands', 'CBD'],
            'itinerary' => [['day' => 1, 'title' => 'Depart', 'detail' => 'Travel to the destination.']],
            'accommodation' => 'Local stay',
            'meals' => ['Breakfast'],
            'transport' => '4x4 vehicle',
            'activities' => ['Guided tour'],
            'included_items' => ['Transport'],
            'excluded_items' => ['Personal purchases'],
            'cancellation_policy' => 'Full refund up to seven days before departure.',
            'status' => ListingStatus::Published,
            'featured' => false,
            'verification_status' => VerificationStatus::Approved,
            'operator_id' => User::factory(),
            'destination_id' => Destination::factory(),
            'category_id' => TripCategory::factory(),
            'required_vehicle_id' => null,
        ];
    }
}
