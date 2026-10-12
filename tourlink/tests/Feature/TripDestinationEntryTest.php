<?php

namespace Tests\Feature;

use App\ListingStatus;
use App\Models\Destination;
use App\Models\Trip;
use App\Models\User;
use App\Role;
use App\Services\Catalog\DestinationResolver;
use App\Services\Catalog\TripCategoryCatalog;
use App\VerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TripDestinationEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_enter_a_destination_name_instead_of_selecting_one(): void
    {
        $operator = User::factory()->create(['role' => Role::Operator]);

        $this->actingAs($operator)
            ->get(route('operator.trips.create'))
            ->assertOk()
            ->assertSee('name="destination_name"', false)
            ->assertSee('data-trip-step="0"', false)
            ->assertSee('data-trip-step="1"', false)
            ->assertSee('data-trip-step="2"', false)
            ->assertSee('data-trip-step="3"', false)
            ->assertSee('data-trip-step="4"', false)
            ->assertSee('Basic trip details')
            ->assertSee('Itinerary builder')
            ->assertSee('Trip media')
            ->assertDontSee('name="destination_id"', false);
    }

    public function test_operator_trip_form_renders_normalized_old_input_after_validation_fails(): void
    {
        $operator = User::factory()->create(['role' => Role::Operator]);

        $this->actingAs($operator)
            ->from(route('operator.trips.create'))
            ->followingRedirects()
            ->post(route('operator.trips.store'), [
                'description' => 'A short trip description.',
                'pickup_points' => "Central station\nAirport",
                'itinerary' => json_encode([
                    ['day' => 1, 'title' => 'Arrival', 'detail' => 'Airport transfer'],
                ]),
                'meals' => "Breakfast\nLunch",
            ])
            ->assertOk()
            ->assertSee('Central station')
            ->assertSee('Airport transfer')
            ->assertSee('Breakfast');
    }

    public function test_operator_can_create_trip_without_submitting_a_slug(): void
    {
        Storage::fake('public');
        $operator = User::factory()->create(['role' => Role::Operator]);
        $category = app(TripCategoryCatalog::class)->all()->first();

        $response = $this->actingAs($operator)
            ->post(route('operator.trips.store'), [
                'name' => 'Savannah Weekend',
                'submission_action' => 'submit',
                'description' => 'A weekend exploring the savannah.',
                'starting_point' => 'Nairobi',
                'ending_point' => 'Maasai Mara',
                'destination_name' => 'Maasai Mara',
                'category_id' => $category->id,
                'duration_days' => 2,
                'departure_date' => '2027-01-10 08:00',
                'return_date' => '2027-01-12 18:00',
                'price_per_person' => 25000,
                'max_travelers' => 10,
                'min_travelers' => 1,
                'available_seats' => 10,
                'pickup_points' => 'Nairobi CBD',
                'itinerary' => json_encode([
                    ['day' => 1, 'title' => 'Arrival', 'detail' => 'Travel to the reserve.'],
                ]),
                'cancellation_policy' => 'Contact us to cancel.',
                'images_files' => [UploadedFile::fake()->image('savannah.jpg')],
            ]);

        $trip = Trip::query()->where('name', 'Savannah Weekend')->firstOrFail();

        $response->assertRedirect(route('operator.trips.index'))
            ->assertSessionHas('status', 'You have successfully created your trip. It has been submitted for administrator approval.');
        $this->assertSame('savannah-weekend', $trip->slug);
        $this->assertSame($operator->id, $trip->operator_id);
        $this->assertSame(ListingStatus::Draft, $trip->status);
        $this->assertSame(VerificationStatus::Pending, $trip->verification_status);
        $this->assertNotNull($trip->submitted_at);
        $this->assertDatabaseCount('trip_images', 1);
        $image = $trip->images()->firstOrFail();
        $this->assertStringStartsWith('/storage/trip-images/', $image->url);
        $this->get($image->url)->assertOk();
    }

    public function test_legacy_trip_upload_urls_are_normalized_to_the_current_host(): void
    {
        $trip = Trip::factory()->create();
        $trip->images()->create([
            'url' => 'https://old-host.example/storage/trip-images/namanga-cover.jpg',
            'alt' => 'Namanga Road Trip',
            'sort_order' => 0,
        ]);

        $this->assertSame('/storage/trip-images/namanga-cover.jpg', $trip->images()->firstOrFail()->url);
    }

    public function test_operator_can_save_a_trip_as_a_private_draft(): void
    {
        $operator = User::factory()->create(['role' => Role::Operator]);
        $category = app(TripCategoryCatalog::class)->all()->first();

        $this->actingAs($operator)
            ->post(route('operator.trips.store'), [
                'submission_action' => 'draft',
                'name' => 'Private Safari Draft',
                'description' => 'A draft experience for a private trip.',
                'starting_point' => 'Nairobi',
                'ending_point' => 'Amboseli',
                'destination_name' => 'Amboseli',
                'category_id' => $category->id,
                'duration_days' => 1,
                'departure_date' => '2027-02-10 08:00',
                'return_date' => '2027-02-10 18:00',
                'price_per_person' => 15000,
                'max_travelers' => 8,
                'min_travelers' => 1,
                'available_seats' => 8,
                'pickup_points' => [],
                'itinerary' => [['day' => 1, 'title' => 'Game drive', 'detail' => 'Explore the park.']],
                'cancellation_policy' => 'Contact us to cancel.',
            ])
            ->assertRedirect(route('operator.trips.index'))
            ->assertSessionHas('status', 'Your trip draft was saved successfully.');

        $trip = Trip::query()->where('name', 'Private Safari Draft')->firstOrFail();
        $this->assertSame(ListingStatus::Draft, $trip->status);
        $this->assertNull($trip->submitted_at);
    }

    public function test_admin_can_enter_a_destination_name_instead_of_selecting_one(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.trips.create'))
            ->assertOk()
            ->assertSee('name="destination_name"', false)
            ->assertSee('Drop images here or browse')
            ->assertSee('data-admin-trip-step="4"', false)
            ->assertSee('name="child_price"', false)
            ->assertSee('name="group_discount_percent"', false)
            ->assertDontSee('JSON array of day, title and detail')
            ->assertDontSee('name="destination_id"', false);
    }

    public function test_entered_destination_is_created_once_and_reused_case_insensitively(): void
    {
        $resolver = app(DestinationResolver::class);

        $destination = $resolver->resolveOrCreate('Nairobi');
        $reusedDestination = $resolver->resolveOrCreate('nAiRoBi');

        $this->assertSame($destination->id, $reusedDestination->id);
        $this->assertSame('Nairobi', $destination->name);
        $this->assertSame('Kenya', $destination->country);
        $this->assertDatabaseCount('destinations', 1);
    }
}
