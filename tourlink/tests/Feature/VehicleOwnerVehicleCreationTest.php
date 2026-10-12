<?php

namespace Tests\Feature;

use App\ListingStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Role;
use App\VerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VehicleOwnerVehicleCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_owner_sees_a_six_step_vehicle_listing_wizard(): void
    {
        $owner = User::factory()->create(['role' => Role::VehicleOwner]);

        $this->actingAs($owner)
            ->get(route('vehicle-owner.vehicles.create'))
            ->assertOk()
            ->assertSee('Vehicle details')
            ->assertSee('Features')
            ->assertSee('Photos')
            ->assertSee('Pricing')
            ->assertSee('Location &amp; availability')
            ->assertSee('Review &amp; submit')
            ->assertSee('data-vehicle-step="5"', false);
    }

    public function test_vehicle_owner_can_create_a_vehicle_with_photos_for_admin_approval(): void
    {
        $owner = User::factory()->create(['role' => Role::VehicleOwner]);

        $response = $this->actingAs($owner)->post(route('vehicle-owner.vehicles.store'), [
            'submission_action' => 'submit',
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
        $image = $vehicle->images()->firstOrFail();
        $this->assertStringStartsWith('/media/vehicle-images/', $image->url);
        $this->assertStringEndsWith('.jpg', $image->url);
        $this->get($image->url)->assertOk();
        $storedImage = public_path('media/'.substr($image->url, strlen('/media/')));
        @unlink($storedImage);
        @rmdir(dirname($storedImage));
        @rmdir(public_path('media'));
    }

    public function test_vehicle_cannot_be_saved_without_at_least_one_photo(): void
    {
        $owner = User::factory()->create(['role' => Role::VehicleOwner]);

        $this->actingAs($owner)->from(route('vehicle-owner.vehicles.create'))->post(route('vehicle-owner.vehicles.store'), [
            'submission_action' => 'draft',
            'name' => 'Photo required vehicle',
            'registration_number' => 'KDA 456B',
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => now()->year,
            'body_type' => 'Safari Van',
            'seating_capacity' => 8,
            'transmission' => 'Automatic',
            'fuel_type' => 'Diesel',
            'price_per_day' => 12000,
            'location' => 'Nairobi',
        ])->assertSessionHasErrors(['images_files' => 'Please upload at least one vehicle photo.']);

        $this->assertDatabaseMissing('vehicles', ['name' => 'Photo required vehicle']);
    }
}
