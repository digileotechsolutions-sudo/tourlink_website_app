<?php

namespace Tests\Feature;

use App\ListingStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Role;
use App\VerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleOwnerVehicleCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_owner_can_create_a_vehicle_with_photos_for_admin_approval(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => Role::VehicleOwner]);

        $response = $this->actingAs($owner)->post(route('vehicle-owner.vehicles.store'), [
            'name' => 'Safari Van',
            'registration_number' => 'KDA 123A',
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => now()->year,
            'body_type' => 'Safari Van',
            'seating_capacity' => 8,
            'transmission' => 'Automatic',
            'fuel_type' => 'Diesel',
            'price_per_day' => 12000,
            'location' => 'Nairobi',
            'images_files' => [UploadedFile::fake()->image('safari-van.jpg')],
        ]);

        $vehicle = Vehicle::query()->where('name', 'Safari Van')->firstOrFail();

        $response->assertRedirect(route('vehicle-owner.vehicles.edit', $vehicle))
            ->assertSessionHas('status', 'Vehicle added successfully. It is waiting for administrator approval.');

        $this->assertSame($owner->id, $vehicle->owner_id);
        $this->assertSame(ListingStatus::Draft, $vehicle->status);
        $this->assertSame(VerificationStatus::Pending, $vehicle->verification_status);
        $this->assertDatabaseCount('vehicle_images', 1);
    }
}
