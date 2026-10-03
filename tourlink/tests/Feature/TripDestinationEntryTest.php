<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\User;
use App\Role;
use App\Services\Catalog\DestinationResolver;
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
