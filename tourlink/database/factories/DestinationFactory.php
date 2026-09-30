<?php

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'slug' => fake()->unique()->slug(),
            'country' => 'Kenya',
            'description' => fake()->sentence(),
            'image_url' => 'https://images.unsplash.com/photo-1516426122078-c23e76319801',
            'location' => fake()->city(),
            'attractions' => ['Local markets', 'Scenic viewpoints'],
            'activities' => ['Guided walks', 'Photography'],
        ];
    }
}
