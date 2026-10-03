<?php

namespace Tests\Feature;

use App\Models\TripCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TripCategoryCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_and_road_trip_categories_are_available_for_trip_discovery(): void
    {
        $expectedCategories = [
            'Safari & Wildlife 🦁',
            'Beach & Coastal Tours 🏖️',
            'Mountain & Hiking ⛰️',
            'Cultural & Heritage Tours 🏛️',
            'City Tours 🏙️',
            'Nature & Adventure 🌿',
            'Food & Culinary Tours 🍽️',
            'Photography Tours 📸',
            'Family Tours 👨‍👩‍👧‍👦',
            'Luxury Tours ✨',
            'Budget Tours 🎒',
            'Camping & Outdoor ⛺',
            'Weekend Getaways',
            'Scenic Drives',
            'Safari Road Trips',
            'Coastal Road Trips',
            'Mountain Road Trips',
            'National Park Trips',
            'Camping Road Trips',
            'Adventure Road Trips',
            'Group Road Trips',
            'Family Road Trips',
            'Motorbike Trips',
            'Cross-Country Trips',
        ];

        foreach ($expectedCategories as $name) {
            $this->assertDatabaseHas('trip_categories', [
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }

        $this->get(route('trips.index'))
            ->assertOk()
            ->assertSee('Safari &amp; Wildlife 🦁')
            ->assertSee('Weekend Getaways');
    }
}
