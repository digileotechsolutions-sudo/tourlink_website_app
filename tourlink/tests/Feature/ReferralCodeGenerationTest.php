<?php

namespace Tests\Feature;

use App\Models\User;
use App\ReferralStatus;
use App\Services\Referral\ReferralService;
use Tests\TestCase;

class ReferralCodeGenerationTest extends TestCase
{
    public function test_a_generated_code_uses_the_configured_prefix_and_length(): void
    {
        $this->setReferralSettings(['code_prefix' => 'KIM', 'code_length' => '8']);

        $service = app(ReferralService::class);
        $code = $service->generateUniqueCode();

        $this->assertStringStartsWith('KIM', $code);
        $this->assertSame(11, strlen($code));
        $this->assertMatchesRegularExpression('/^KIM[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{8}$/', $code);
    }

    public function test_generated_codes_never_exclude_a_live_code(): void
    {
        $this->setReferralSettings(['code_prefix' => 'TL', 'code_length' => '6']);

        User::factory()->create(['referral_code' => 'TLZZZZZ']);

        $service = app(ReferralService::class);

        $this->assertNotSame('TLZZZZZ', $service->generateUniqueCode());
    }

    public function test_ensure_code_for_is_idempotent(): void
    {
        $this->setReferralSettings();

        $user = User::factory()->create(['referral_code' => null]);
        $service = app(ReferralService::class);

        $first = $service->ensureCodeFor($user);
        $second = $service->ensureCodeFor($user);

        $this->assertNotEmpty($first);
        $this->assertSame($first, $second);
        $this->assertSame($first, $user->refresh()->referral_code);
    }

    public function test_share_url_carries_the_resolved_code(): void
    {
        $this->setReferralSettings();

        $user = User::factory()->create(['referral_code' => 'TLABC234']);

        $this->assertStringEndsWith('/register?ref=TLABC234', app(ReferralService::class)->shareUrlFor($user));
    }

    public function test_codes_are_case_insensitive_when_resolved(): void
    {
        $this->setReferralSettings();

        $referrer = $this->referrerUser(['referral_code' => 'TLABC234']);

        $this->assertTrue($referrer->is(app(ReferralService::class)->findByCode('tlabc234')));
        $this->assertTrue($referrer->is(app(ReferralService::class)->findByCode(' TLABC234 ')));
        $this->assertNull(app(ReferralService::class)->findByCode('TLNOPE00'));
    }

    public function test_backfill_command_assigns_codes_to_existing_users(): void
    {
        $this->setReferralSettings();

        User::factory()->count(3)->create(['referral_code' => null]);

        $this->artisan('referrals:backfill-codes')->assertSuccessful();

        $codes = User::query()->pluck('referral_code');

        $this->assertCount(3, $codes->filter());
        $this->assertCount(3, $codes->unique());
    }

    public function test_backfill_command_dry_run_writes_nothing(): void
    {
        $this->setReferralSettings();

        User::factory()->create(['referral_code' => null]);

        $this->artisan('referrals:backfill-codes --dry-run')->assertSuccessful();

        $this->assertNull(User::query()->value('referral_code'));
    }

    public function test_registration_page_credits_a_valid_referral_link(): void
    {
        $this->setReferralSettings();

        $referrer = $this->referrerUser();

        $this->get(route('register', ['ref' => 'tlabc234']))
            ->assertOk()
            ->assertSee($referrer->name)
            ->assertSee('invited you to join TourLink');

        $this->assertSame($referrer->id, session(ReferralService::SESSION_KEY));
    }

    public function test_registration_page_ignores_an_unknown_referral_link(): void
    {
        $this->setReferralSettings();

        $this->get(route('register', ['ref' => 'TLNOPE00']))
            ->assertOk()
            ->assertDontSee('invited you to join TourLink');

        $this->assertNull(session(ReferralService::SESSION_KEY));
    }

    public function test_a_referral_is_never_recorded_when_the_programme_is_disabled(): void
    {
        $this->setReferralSettings(['enabled' => '0']);

        $referrer = $this->referrerUser();
        $referred = User::factory()->create(['referral_code' => 'TLXYZ789']);

        $this->assertNull(app(ReferralService::class)->recordForNewUser($referred, $referrer));
    }

    public function test_a_person_cannot_refer_themselves(): void
    {
        $this->setReferralSettings();

        $user = $this->referrerUser(['referral_code' => 'TLABC234', 'email' => 'me@example.com']);

        $this->assertNull(app(ReferralService::class)->recordForNewUser($user, $user));
    }

    public function test_a_referral_cannot_be_re_pointed_to_another_referrer(): void
    {
        $this->setReferralSettings();

        $first = $this->referrerUser(['referral_code' => 'TLA11111']);
        $second = $this->referrerUser(['referral_code' => 'TLB22222']);
        $referred = User::factory()->create(['referral_code' => 'TLXYZ789']);

        $service = app(ReferralService::class);

        $this->assertNotNull($service->recordForNewUser($referred, $first));
        $this->assertNull($service->recordForNewUser($referred, $second));
        $this->assertSame(1, $referred->referral()->count());
    }

    public function test_referral_record_starts_pending_with_the_referrer_snapshot(): void
    {
        $this->setReferralSettings();

        $referrer = $this->referrerUser();
        $referred = User::factory()->create(['referral_code' => 'TLXYZ789']);

        $referral = app(ReferralService::class)->recordForNewUser($referred, $referrer);

        $this->assertNotNull($referral);
        $this->assertSame(ReferralStatus::Pending, $referral->status);
        $this->assertSame('TLABC234', $referral->referral_code);
        $this->assertTrue($referrer->is($referral->referrer));
        $this->assertTrue($referred->is($referral->referredUser));
    }
}
