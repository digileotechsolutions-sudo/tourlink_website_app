<?php

namespace Tests\Feature;

use App\AccountApprovalStatus;
use App\Models\Referral;
use App\Models\User;
use App\Models\VerificationRequest;
use App\ReferralStatus;
use App\Services\Referral\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralApprovalHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_user_through_admin_moves_the_referral_forward(): void
    {
        $this->setReferralSettings();

        $referral = $this->referralFor();
        $referred = $referral->referredUser;

        $this->actingAs($this->admin())->put(route('admin.users.update', $referred), [
            'name' => $referred->name,
            'email' => $referred->email,
            'phone' => $referred->phone,
            'role' => $referred->role->value,
            'approval_status' => AccountApprovalStatus::Approved->value,
            'account_status' => $referred->account_status->value,
            'verification_level' => $referred->verification_level->value,
        ]);

        $this->assertSame(ReferralStatus::Approved, $referral->refresh()->status);
    }

    public function test_approving_a_verification_request_moves_the_referral_forward(): void
    {
        $this->setReferralSettings();

        $referral = $this->referralFor();

        $request = VerificationRequest::query()->create([
            'user_id' => $referral->referred_user_id,
            'type' => 'OPERATOR',
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->admin())->patch(route('admin.verification.update', $request), [
            'status' => 'APPROVED',
        ]);

        $this->assertSame(ReferralStatus::Approved, $referral->refresh()->status);
    }

    public function test_rejecting_an_account_leaves_the_referral_rejected(): void
    {
        $this->setReferralSettings();

        $referral = $this->referralFor();
        $referred = $referral->referredUser;

        $this->actingAs($this->admin())->put(route('admin.users.update', $referred), [
            'name' => $referred->name,
            'email' => $referred->email,
            'phone' => $referred->phone,
            'role' => $referred->role->value,
            'approval_status' => AccountApprovalStatus::Rejected->value,
            'account_status' => $referred->account_status->value,
            'verification_level' => $referred->verification_level->value,
        ]);

        $this->assertSame(ReferralStatus::Rejected, $referral->refresh()->status);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'ADMIN', 'referral_code' => 'TLADMIN1']);
    }

    private function referralFor(): Referral
    {
        $referrer = $this->referrerUser();
        $referred = User::factory()->create([
            'referral_code' => 'TLXYZ789',
            'role' => 'OPERATOR',
            'approval_status' => AccountApprovalStatus::Pending,
        ]);

        return app(ReferralService::class)->recordForNewUser($referred, $referrer);
    }
}
