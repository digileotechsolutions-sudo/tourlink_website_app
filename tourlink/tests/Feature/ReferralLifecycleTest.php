<?php

namespace Tests\Feature;

use App\AccountApprovalStatus;
use App\Models\User;
use App\ReferralRewardStatus;
use App\ReferralStatus;
use App\Services\Referral\ReferralService;
use Tests\TestCase;

class ReferralLifecycleTest extends TestCase
{
    public function test_email_verification_moves_the_referral_to_verified(): void
    {
        $this->setReferralSettings();

        [$referral] = $this->pendingReferral();

        $this->assertNotNull(app(ReferralService::class)->markVerified($referral->referredUser));

        $referral->refresh();

        $this->assertSame(ReferralStatus::Verified, $referral->status);
        $this->assertNotNull($referral->verified_at);
    }

    public function test_admin_approval_moves_the_referral_to_approved(): void
    {
        $this->setReferralSettings();

        [$referral] = $this->pendingReferral();

        app(ReferralService::class)->markVerified($referral->referredUser);
        app(ReferralService::class)->markApproved($referral->referredUser);

        $referral->refresh();

        $this->assertSame(ReferralStatus::Approved, $referral->status);
        $this->assertNotNull($referral->approved_at);
    }

    public function test_a_referral_is_rejected_when_the_account_is_rejected(): void
    {
        $this->setReferralSettings();

        [$referral] = $this->pendingReferral();

        $service = app(ReferralService::class);
        $service->markVerified($referral->referredUser);

        $service->markRejected($referral->referredUser);

        $referral->refresh();

        $this->assertSame(ReferralStatus::Rejected, $referral->status);
        $this->assertSame(ReferralRewardStatus::Rejected, $referral->reward_status);
    }

    public function test_a_settled_referral_is_never_promoted_again(): void
    {
        $this->setReferralSettings();

        [$referral] = $this->pendingReferral();

        $service = app(ReferralService::class);
        $service->markRejected($referral->referredUser);

        $this->assertNull($service->markApproved($referral->referredUser));
        $this->assertSame(ReferralStatus::Rejected, $referral->refresh()->status);
    }

    public function test_no_reward_is_issued_while_rewards_are_disabled(): void
    {
        $this->setReferralSettings(['reward_enabled' => '0']);

        [$referral] = $this->pendingReferral();

        app(ReferralService::class)->markApproved($referral->referredUser);

        $referral->refresh();

        $this->assertSame(ReferralStatus::Approved, $referral->status);
        $this->assertSame(ReferralRewardStatus::None, $referral->reward_status);
        $this->assertSame(0, $referral->reward_amount);
    }

    public function test_a_reward_waits_for_manual_approval_by_default(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '1']);

        [$referral] = $this->pendingReferral();

        app(ReferralService::class)->markApproved($referral->referredUser);

        $referral->refresh();

        $this->assertSame(ReferralStatus::Approved, $referral->status);
        $this->assertSame(ReferralRewardStatus::Pending, $referral->reward_status);
        $this->assertSame(100, $referral->reward_amount);
        $this->assertNull($referral->rewarded_at);
    }

    public function test_a_reward_is_paid_immediately_when_no_review_is_required(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '0']);

        [$referral] = $this->pendingReferral();

        app(ReferralService::class)->markApproved($referral->referredUser);

        $referral->refresh();

        $this->assertSame(ReferralStatus::Rewarded, $referral->status);
        $this->assertSame(ReferralRewardStatus::Paid, $referral->reward_status);
        $this->assertNotNull($referral->rewarded_at);
    }

    public function test_the_trigger_can_fire_on_email_verification(): void
    {
        $this->setReferralSettings([
            'reward_enabled' => '1',
            'reward_requires_approval' => '0',
            'reward_trigger' => ReferralStatus::Verified->value,
        ]);

        [$referral] = $this->pendingReferral();

        app(ReferralService::class)->markVerified($referral->referredUser);

        $referral->refresh();

        $this->assertSame(ReferralStatus::Rewarded, $referral->status);
        $this->assertSame(ReferralRewardStatus::Paid, $referral->reward_status);
    }

    public function test_approval_does_not_pay_a_reward_twice(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '0']);

        [$referral] = $this->pendingReferral();
        $service = app(ReferralService::class);

        $service->markApproved($referral->referredUser);
        $firstPaidAt = $referral->refresh()->rewarded_at;

        $service->markApproved($referral->referredUser);
        $service->maybeIssueReward($referral);

        $referral->refresh();

        $this->assertSame(ReferralRewardStatus::Paid, $referral->reward_status);
        $this->assertSame(100, $referral->reward_amount);
        $this->assertEquals($firstPaidAt, $referral->rewarded_at);
    }

    public function test_statistics_only_count_successful_referrals(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '0']);

        $referrer = $this->referrerUser();
        $service = app(ReferralService::class);

        $pending = $service->recordForNewUser(User::factory()->create(['referral_code' => 'TLP11111']), $referrer);
        $verified = $service->recordForNewUser(User::factory()->create(['referral_code' => 'TLV22222']), $referrer);
        $rewarded = $service->recordForNewUser(User::factory()->create(['referral_code' => 'TLR33333']), $referrer);

        $service->markVerified($verified->referredUser);
        $service->markApproved($rewarded->referredUser);

        $statistics = $service->statisticsFor($referrer);

        $this->assertSame(3, $statistics['total']);
        $this->assertSame(1, $statistics['pending']);
        $this->assertSame(2, $statistics['verified']);
        $this->assertSame(1, $statistics['successful']);
        $this->assertSame(100, $statistics['rewards']);
        $this->assertSame(0, $statistics['pending_rewards']);
        $this->assertNull($pending->rewarded_at);
    }

    public function test_the_referrer_dashboard_is_available_to_every_role(): void
    {
        $this->setReferralSettings();

        foreach (['TRAVELER', 'OPERATOR', 'VEHICLE_OWNER', 'ADMIN'] as $role) {
            $user = User::factory()->create(['role' => $role, 'referral_code' => 'TL'.str_pad($role, 5, 'X', STR_PAD_LEFT)]);

            $this->actingAs($user)
                ->get(route('referrals.index'))
                ->assertOk()
                ->assertSee('Invite friends, earn rewards');
        }
    }

    public function test_the_referral_dashboard_requires_an_authenticated_account(): void
    {
        $this->get(route('referrals.index'))->assertRedirect(route('login'));
    }

    public function test_an_unapproved_account_cannot_open_the_referral_dashboard(): void
    {
        $this->setReferralSettings();

        $user = User::factory()->create([
            'role' => 'TRAVELER',
            'approval_status' => AccountApprovalStatus::Pending,
            'referral_code' => 'TLPEND99',
        ]);

        $this->actingAs($user)->get(route('referrals.index'))->assertRedirect(route('login'));
    }

    /**
     * @return array{0: \App\Models\Referral, 1: \App\Models\User}
     */
    private function pendingReferral(): array
    {
        $referrer = $this->referrerUser();
        $referred = User::factory()->create(['referral_code' => 'TLXYZ789']);

        return [app(ReferralService::class)->recordForNewUser($referred, $referrer), $referrer];
    }
}
