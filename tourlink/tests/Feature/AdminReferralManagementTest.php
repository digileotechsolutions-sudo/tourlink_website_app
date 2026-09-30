<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\User;
use App\ReferralRewardStatus;
use App\ReferralStatus;
use App\Services\Referral\ReferralService;
use Tests\TestCase;

class AdminReferralManagementTest extends TestCase
{
    public function test_admins_can_review_referrals(): void
    {
        $this->setReferralSettings();

        $this->actingAs($this->admin())
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertSee('Referrals &amp; rewards');
    }

    public function test_non_admins_cannot_reach_referral_management(): void
    {
        $this->setReferralSettings();

        $this->actingAs(User::factory()->create(['role' => 'TRAVELER']))
            ->get(route('admin.referrals.index'))
            ->assertForbidden();
    }

    public function test_settings_are_saved_and_cached_settings_are_refreshed(): void
    {
        $this->setReferralSettings();

        $this->actingAs($this->admin())
            ->from(route('admin.referrals.index'))
            ->put(route('admin.referrals.settings'), [
                'enabled' => '1',
                'reward_enabled' => '1',
                'reward_amount' => '250',
                'reward_currency' => 'usd',
                'reward_trigger' => ReferralStatus::Verified->value,
                'reward_requires_approval' => '1',
                'code_prefix' => 'kim',
                'code_length' => '8',
            ])
            ->assertRedirect(route('admin.referrals.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'referral.reward_amount', 'value' => '250']);
        $this->assertDatabaseHas('settings', ['key' => 'referral.reward_currency', 'value' => 'USD']);
        $this->assertDatabaseHas('settings', ['key' => 'referral.code_prefix', 'value' => 'KIM']);
        $this->assertDatabaseHas('settings', ['key' => 'referral.code_length', 'value' => '8']);

        $this->assertSame('KIM', app(\App\Services\Referral\ReferralSettings::class)->codePrefix());
        $this->assertSame(250, app(\App\Services\Referral\ReferralSettings::class)->rewardAmount());
    }

    public function test_an_invalid_code_prefix_is_rejected(): void
    {
        $this->setReferralSettings();

        $this->actingAs($this->admin())
            ->from(route('admin.referrals.index'))
            ->put(route('admin.referrals.settings'), [
                'reward_trigger' => ReferralStatus::Approved->value,
                'code_prefix' => '12',
            ])
            ->assertSessionHasErrors('code_prefix');
    }

    public function test_an_admin_can_release_a_pending_reward(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '1']);

        [$referral] = $this->queuedReward();

        $this->actingAs($this->admin())
            ->from(route('admin.referrals.index'))
            ->post(route('admin.referrals.reward', $referral), ['reward_status' => 'APPROVED'])
            ->assertRedirect(route('admin.referrals.index'));

        $referral->refresh();

        $this->assertSame(ReferralStatus::Rewarded, $referral->status);
        $this->assertSame(ReferralRewardStatus::Paid, $referral->reward_status);
        $this->assertNotNull($referral->rewarded_at);
        $this->assertDatabaseHas('admin_logs', ['action' => 'referral.reward_approved']);
    }

    public function test_an_admin_can_reject_a_pending_reward(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '1']);

        [$referral] = $this->queuedReward();

        $this->actingAs($this->admin())
            ->post(route('admin.referrals.reward', $referral), ['reward_status' => 'REJECTED'])
            ->assertRedirect(route('admin.referrals.index'));

        $referral->refresh();

        $this->assertSame(ReferralStatus::Approved, $referral->status);
        $this->assertSame(ReferralRewardStatus::Rejected, $referral->reward_status);
        $this->assertNull($referral->rewarded_at);
    }

    public function test_a_reward_cannot_be_released_twice(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '1']);

        [$referral] = $this->queuedReward();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.referrals.reward', $referral), ['reward_status' => 'APPROVED']);
        $this->actingAs($admin)->post(route('admin.referrals.reward', $referral), ['reward_status' => 'APPROVED']);

        $this->assertSame(ReferralRewardStatus::Paid, $referral->refresh()->reward_status);
        $this->assertSame(1, Referral::query()->count());
    }

    public function test_an_invalid_reward_decision_is_rejected(): void
    {
        $this->setReferralSettings(['reward_enabled' => '1', 'reward_requires_approval' => '1']);

        [$referral] = $this->queuedReward();

        $this->actingAs($this->admin())
            ->from(route('admin.referrals.index'))
            ->post(route('admin.referrals.reward', $referral), ['reward_status' => 'PAID'])
            ->assertSessionHasErrors('reward_status');

        $this->assertSame(ReferralRewardStatus::Pending, $referral->refresh()->reward_status);
    }

    public function test_the_referral_list_can_be_filtered(): void
    {
        $this->setReferralSettings();

        $referrer = $this->referrerUser();
        app(ReferralService::class)->recordForNewUser(User::factory()->create(['referral_code' => 'TLP11111']), $referrer);

        $this->actingAs($this->admin())
            ->get(route('admin.referrals.index', ['status' => ReferralStatus::Approved->value]))
            ->assertOk()
            ->assertDontSee('No referrals match these filters.');

        $this->actingAs($this->admin())
            ->get(route('admin.referrals.index', ['search' => 'TLABC234']))
            ->assertOk()
            ->assertDontSee('No referrals match these filters.');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'ADMIN',
            'referral_code' => 'TLADMIN1',
        ]);
    }

    /**
     * @return array{0: Referral, 1: User}
     */
    private function queuedReward(): array
    {
        $referrer = $this->referrerUser();
        $referred = User::factory()->create(['referral_code' => 'TLXYZ789']);
        $service = app(ReferralService::class);

        $referral = $service->recordForNewUser($referred, $referrer);
        $service->markVerified($referred);
        $service->markApproved($referred);

        return [$referral->refresh(), $referrer];
    }
}
