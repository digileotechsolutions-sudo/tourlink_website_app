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
        $operator = User::factory()->create(['role' => Role::Operator]);
        $category = app(TripCategoryCatalog::class)->all()->first();

        $response = $this->actingAs($operator)
            ->post(route('operator.trips.store'), [
                'name' => 'Savannah Weekend',
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
            ]);

        $trip = Trip::query()->where('name', 'Savannah Weekend')->firstOrFail();

        $response->assertRedirect(route('operator.trips.index'))
            ->assertSessionHas('status', 'Trip created and submitted for verification.');
        $this->assertSame('savannah-weekend', $trip->slug);
        $this->assertSame($operator->id, $trip->operator_id);
        $this->assertSame(ListingStatus::Draft, $trip->status);
        $this->assertSame(VerificationStatus::Pending, $trip->verification_status);
    }

    public function test_admin_can_enter_a_destination_name_instead_of_selecting_one(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.trips.create'))
            ->assertOk()
            ->assertSee('name="destination_name"', false)
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
