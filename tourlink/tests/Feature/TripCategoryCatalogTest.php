<?php

namespace Tests\Feature;

use App\Models\TripCategory;
use App\Models\User;
use App\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TripCategoryCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_and_road_trip_categories_are_available_for_trip_discovery(): void
    {
        $expectedCategories = [
            'Safari & Wildlife',
            'Beach & Coastal Tours',
            'Mountain & Hiking',
            'Cultural & Heritage Tours',
            'City Tours',
            'Nature & Adventure',
            'Food & Culinary Tours',
            'Photography Tours',
            'Family Tours',
            'Luxury Tours',
            'Budget Tours',
            'Camping & Outdoor',
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
            ->assertSee('Safari &amp; Wildlife')
            ->assertSee('Weekend Getaways');
    }

    public function test_category_catalog_repairs_missing_defaults_when_a_trip_page_is_loaded(): void
    {
        TripCategory::query()->delete();

        $this->get(route('trips.index'))
            ->assertOk()
            ->assertSee('Safari &amp; Wildlife')
            ->assertSee('Cross-Country Trips');

        $this->assertDatabaseCount('trip_categories', 24);
    }

    public function test_repair_migration_restores_categories_without_replacing_existing_category_ids(): void
    {
        $category = TripCategory::query()->where('slug', 'safari-wildlife')->firstOrFail();
        $category->update(['name' => 'Outdated safari name']);
        TripCategory::query()->where('slug', 'beach-coastal-tours')->delete();

        $migration = require database_path('migrations/2026_10_03_180000_repair_trip_and_road_trip_categories.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseCount('trip_categories', 24);
        $this->assertDatabaseHas('trip_categories', [
            'id' => $category->id,
            'name' => 'Safari & Wildlife',
            'slug' => 'safari-wildlife',
        ]);
        $this->assertDatabaseHas('trip_categories', [
            'name' => 'Beach & Coastal Tours',
            'slug' => 'beach-coastal-tours',
        ]);
    }

    public function test_operator_trip_form_repairs_missing_default_categories(): void
    {
        TripCategory::query()->delete();
        $operator = User::factory()->create(['role' => Role::Operator]);

        $this->actingAs($operator)
            ->get(route('operator.trips.create'))
            ->assertOk()
            ->assertSee('Safari &amp; Wildlife')
            ->assertSee('Cross-Country Trips');

        $this->assertDatabaseCount('trip_categories', 24);
    }

    public function test_admin_trip_form_repairs_missing_default_categories(): void
    {
        TripCategory::query()->delete();
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.trips.create'))
            ->assertOk()
            ->assertSee('Safari &amp; Wildlife')
            ->assertSee('Cross-Country Trips');

        $this->assertDatabaseCount('trip_categories', 24);
    }

    public function test_category_catalog_removes_emojis_from_existing_default_category_names(): void
    {
        TripCategory::query()->create([
            'name' => 'Safari & Wildlife 🦁',
            'slug' => Str::slug('Safari & Wildlife'),
        ]);

        app(\App\Services\Catalog\TripCategoryCatalog::class)->all();

        $this->assertDatabaseHas('trip_categories', [
            'name' => 'Safari & Wildlife',
            'slug' => Str::slug('Safari & Wildlife'),
        ]);
        $this->assertDatabaseMissing('trip_categories', ['name' => 'Safari & Wildlife 🦁']);
    }
}
