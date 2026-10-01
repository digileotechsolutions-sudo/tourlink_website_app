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

    public function test_optional_driving_licence_is_saved_and_admin_can_open_owner_documents(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $owner = User::factory()->create(['role' => 'VEHICLE_OWNER']);

        $this->actingAs($owner)
            ->post(route('vehicle-owner.verification.identity.store'), [
                'documents' => [
                    'owner_id' => UploadedFile::fake()->create('owner-id.pdf', 100, 'application/pdf'),
                    'driving_license' => UploadedFile::fake()->create('driving-licence.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $verificationRequest = VerificationRequest::query()
            ->where('user_id', $owner->id)
            ->where('type', 'VEHICLE_OWNER_IDENTITY')
            ->firstOrFail();

        $this->assertCount(2, $verificationRequest->documents);

        $this->actingAs($admin)
            ->get(route('admin.verification.document', [$verificationRequest, 'driving_license']))
            ->assertOk();
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

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($admin)
            ->get(route('admin.verification.index'))
            ->assertOk()
            ->assertSee($vehicle->registration_number)
            ->assertSee($vehicle->make)
            ->assertSee($vehicle->model)
            ->assertSee((string) $vehicle->year)
            ->assertSee((string) $vehicle->seating_capacity)
            ->assertSee($vehicle->body_type);
    }

    public function test_optional_inspection_certificate_is_stored_and_admin_can_open_vehicle_photos(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $owner = User::factory()->create(['role' => 'VEHICLE_OWNER']);
        $vehicle = Vehicle::factory()->create(['owner_id' => $owner->id]);
        $documents = $this->vehicleDocuments();
        $documents['inspection'] = UploadedFile::fake()->create('inspection.pdf', 100, 'application/pdf');

        $this->actingAs($owner)
            ->post(route('vehicle-owner.verification.vehicle.store'), [
                'vehicle_id' => $vehicle->id,
                'documents' => $documents,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $verificationRequest = VerificationRequest::query()
            ->where('user_id', $owner->id)
            ->where('type', 'VEHICLE')
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();

        $this->assertCount(9, $verificationRequest->documents);

        $this->actingAs($admin)
            ->get(route('admin.verification.index'))
            ->assertOk()
            ->assertSee($vehicle->registration_number);

        $this->get(route('admin.verification.document', [$verificationRequest, 'front_photo']))
            ->assertOk();

        $this->get(route('admin.verification.document', [$verificationRequest, 'inspection']))
            ->assertOk();

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
            'front_photo' => UploadedFile::fake()->create('front.png', 100, 'image/png'),
            'rear_photo' => UploadedFile::fake()->create('rear.png', 100, 'image/png'),
            'left_photo' => UploadedFile::fake()->create('left.png', 100, 'image/png'),
            'right_photo' => UploadedFile::fake()->create('right.png', 100, 'image/png'),
            'interior_photo' => UploadedFile::fake()->create('interior.png', 100, 'image/png'),
            'number_plate_photo' => UploadedFile::fake()->create('plate.png', 100, 'image/png'),
        ];
    }
}
