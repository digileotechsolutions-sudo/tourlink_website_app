<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationRequest;
use App\VerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperatorVerificationDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.otp.delivery' => 'log']);
        $this->setReferralSettings();
        Storage::fake('local');
    }

    public function test_operator_must_submit_every_required_document(): void
    {
        $operator = User::factory()->create(['role' => 'OPERATOR']);

        $this->actingAs($operator)
            ->post(route('operator.verification.store'), [
                'documents' => ['kra_pin' => UploadedFile::fake()->create('kra.pdf', 100, 'application/pdf')],
                'company_profile' => 'We arrange guided tours around Kenya.',
            ])
            ->assertSessionHasErrors([
                'documents.registration_certificate',
                'documents.tour_operator_license',
                'documents.business_permit',
                'documents.representative_id',
                'documents.business_address_proof',
            ]);

        $this->assertDatabaseCount('verification_requests', 0);
    }

    public function test_operator_documents_are_stored_privately_and_request_is_pending(): void
    {
        $operator = User::factory()->create(['role' => 'OPERATOR']);

        $this->actingAs($operator)
            ->post(route('operator.verification.store'), [
                'documents' => $this->documents(),
                'company_profile' => 'We arrange guided tours around Kenya.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $verificationRequest = VerificationRequest::query()
            ->where('user_id', $operator->id)
            ->where('type', 'OPERATOR')
            ->firstOrFail();

        $this->assertSame(VerificationStatus::Pending, $verificationRequest->status);
        $this->assertCount(6, $verificationRequest->documents);

        foreach ($verificationRequest->documents as $document) {
            $this->assertStringStartsWith('verification-documents/operator/', $document['path']);
            $this->assertTrue(Storage::disk('local')->exists($document['path']));
        }
    }

    public function test_admin_cannot_approve_operator_request_without_all_documents(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $verificationRequest = VerificationRequest::query()->create([
            'user_id' => $operator->id,
            'type' => 'OPERATOR',
            'status' => VerificationStatus::Pending,
            'documents' => [],
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.verification.update', $verificationRequest), [
                'status' => VerificationStatus::Approved->value,
                'notes' => 'Looks good',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(VerificationStatus::Pending, $verificationRequest->refresh()->status);
    }

    public function test_bank_details_are_optional_and_admin_can_mark_request_under_review(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);

        $this->actingAs($operator)
            ->get(route('operator.profile'))
            ->assertOk()
            ->assertSee('Business address proof')
            ->assertSee('Business bank confirmation');

        $this->actingAs($operator)
            ->post(route('operator.verification.store'), [
                'documents' => $this->documents(),
                'company_profile' => 'We arrange guided tours around Kenya.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $verificationRequest = VerificationRequest::query()
            ->where('user_id', $operator->id)
            ->where('type', 'OPERATOR')
            ->firstOrFail();

        $this->assertCount(6, $verificationRequest->documents);
        $this->assertSame('We arrange guided tours around Kenya.', $operator->fresh()->operatorProfile->description);

        $this->actingAs($admin)
            ->patch(route('admin.verification.update', $verificationRequest), [
                'status' => VerificationStatus::UnderReview->value,
                'notes' => 'Documents are being reviewed.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(VerificationStatus::UnderReview, $verificationRequest->refresh()->status);

        $this->actingAs($admin)
            ->get(route('admin.verification.index'))
            ->assertOk()
            ->assertSee('🔵 Under Review')
            ->assertSee('⚠️ Documents Required');

        $this->patch(route('admin.verification.update', $verificationRequest), [
            'status' => VerificationStatus::Approved->value,
            'notes' => 'All required documents have been verified.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(VerificationStatus::Approved, $verificationRequest->refresh()->status);
    }

    /** @return array<string, UploadedFile> */
    private function documents(): array
    {
        return [
            'registration_certificate' => UploadedFile::fake()->create('registration.pdf', 150, 'application/pdf'),
            'kra_pin' => UploadedFile::fake()->create('kra.pdf', 100, 'application/pdf'),
            'tour_operator_license' => UploadedFile::fake()->create('operator-license.pdf', 120, 'application/pdf'),
            'business_permit' => UploadedFile::fake()->create('business-permit.pdf', 130, 'application/pdf'),
            'representative_id' => UploadedFile::fake()->create('representative-id.jpg', 100, 'image/jpeg'),
            'business_address_proof' => UploadedFile::fake()->create('business-address.pdf', 120, 'application/pdf'),
        ];
    }
}
