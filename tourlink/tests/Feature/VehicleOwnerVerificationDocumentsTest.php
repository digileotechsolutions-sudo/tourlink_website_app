<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VerificationRequest;
use App\VerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleOwnerVerificationDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.otp.delivery' => 'log']);
        $this->setReferralSettings();
        Storage::fake('local');
    }

    public function test_owner_identity_packet_requires_id_or_passport(): void
    {
        $owner = User::factory()->create(['role' => 'VEHICLE_OWNER']);

        $this->actingAs($owner)
            ->post(route('vehicle-owner.verification.identity.store'))
            ->assertSessionHasErrors('documents.owner_id');

        $this->assertDatabaseCount('verification_requests', 0);
    }

    public function test_vehicle_packet_stores_private_documents_against_owned_vehicle(): void
    {
        $owner = User::factory()->create(['role' => 'VEHICLE_OWNER']);
        $vehicle = Vehicle::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('vehicle-owner.verification.vehicle.store'), [
                'vehicle_id' => $vehicle->id,
                'documents' => $this->vehicleDocuments(),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $verificationRequest = VerificationRequest::query()
            ->where('user_id', $owner->id)
            ->where('type', 'VEHICLE')
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();

        $this->assertSame(VerificationStatus::Pending, $verificationRequest->status);
        $this->assertCount(8, $verificationRequest->documents);
        foreach ($verificationRequest->documents as $document) {
            $this->assertStringStartsWith('verification-documents/vehicle-owner/vehicles/'.$vehicle->id.'/', $document['path']);
            $this->assertTrue(Storage::disk('local')->exists($document['path']));
        }
        $this->assertSame(VerificationStatus::Pending, $vehicle->refresh()->verification_status);
    }

    public function test_vehicle_owner_cannot_submit_documents_for_another_owners_vehicle(): void
    {
        $owner = User::factory()->create(['role' => 'VEHICLE_OWNER']);
        $otherOwner = User::factory()->create(['role' => 'VEHICLE_OWNER']);
        $vehicle = Vehicle::factory()->create(['owner_id' => $otherOwner->id]);

        $this->actingAs($owner)
            ->post(route('vehicle-owner.verification.vehicle.store'), [
                'vehicle_id' => $vehicle->id,
                'documents' => $this->vehicleDocuments(),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('verification_requests', 0);
    }

    public function test_admin_cannot_approve_vehicle_request_without_required_files(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $owner = User::factory()->create(['role' => 'VEHICLE_OWNER']);
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'DRAFT',
            'verification_status' => VerificationStatus::Pending,
        ]);
        $verificationRequest = VerificationRequest::query()->create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'type' => 'VEHICLE',
            'status' => VerificationStatus::Pending,
            'documents' => [],
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.verification.update', $verificationRequest), [
                'status' => VerificationStatus::Approved->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(VerificationStatus::Pending, $verificationRequest->refresh()->status);
        $this->assertSame(VerificationStatus::Pending, $vehicle->refresh()->verification_status);
    }

    /** @return array<string, UploadedFile> */
    private function vehicleDocuments(): array
    {
        return [
            'logbook' => UploadedFile::fake()->create('logbook.pdf', 100, 'application/pdf'),
            'insurance' => UploadedFile::fake()->create('insurance.pdf', 100, 'application/pdf'),
            'front_photo' => UploadedFile::fake()->image('front.png'),
            'rear_photo' => UploadedFile::fake()->image('rear.png'),
            'left_photo' => UploadedFile::fake()->image('left.png'),
            'right_photo' => UploadedFile::fake()->image('right.png'),
            'interior_photo' => UploadedFile::fake()->image('interior.png'),
            'number_plate_photo' => UploadedFile::fake()->image('plate.png'),
        ];
    }
}
