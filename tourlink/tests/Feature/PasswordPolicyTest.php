<?php

namespace Tests\Feature;

use App\Models\User;
use App\OtpChannel;
use App\Http\Middleware\RequireTermsAcceptance;
use App\Services\Verification\AccountVerificationService;
use App\Services\Verification\OtpDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.otp.delivery' => 'log']);
        $this->setReferralSettings();
        Http::fake(['*' => Http::response('', 200)]);
        $this->withCookie(RequireTermsAcceptance::COOKIE_NAME, RequireTermsAcceptance::tokenFor());

        // Registration and reset endpoints are throttled per IP, which a test
        // loop exhausts within a few requests; rate limiting is covered
        // elsewhere, so it is switched off here.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_registration_accepts_a_strong_password_and_stores_only_a_hash(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Amina Kariuki',
            'email' => 'amina@example.com',
            'phone' => '0712345678',
            'terms_accepted' => '1',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
            'role' => 'TRAVELER',
        ]);

        $user = User::query()->firstOrFail();
        $response->assertRedirect(route('verification.notice', ['user' => $user->id]));
        $this->assertTrue(Hash::check('Jungle@Trail29', $user->password));
        $this->assertNotSame('Jungle@Trail29', $user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('PENDING', $user->approval_status->value);
        $this->assertDatabaseHas('otp_challenges', [
            'user_id' => $user->id,
            'channel' => OtpChannel::Email->value,
            'purpose' => 'account_verification',
            'consumed_at' => null,
        ]);
        $this->assertDatabaseMissing('otp_challenges', ['user_id' => $user->id, 'channel' => OtpChannel::Phone->value]);
    }

    public function test_registration_rejects_missing_terms_acceptance_without_creating_an_account(): void
    {
        $this->post(route('register'), [
            'name' => 'Amina Kariuki',
            'email' => 'amina@example.com',
            'phone' => '0712345678',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
            'role' => 'TRAVELER',
        ])->assertSessionHasErrors('terms_accepted');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_page_explains_when_verification_delivery_is_unavailable(): void
    {
        config(['services.otp.delivery' => 'off']);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Signup is temporarily unavailable because email verification delivery is not configured.');
    }

    public function test_log_only_otp_delivery_is_not_available_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        config([
            'app.env' => 'production',
            'services.otp.delivery' => 'log',
            'services.resend.key' => null,
            'services.resend.from' => null,
            'mail.default' => 'log',
        ]);

        $delivery = app(OtpDeliveryService::class);

        $this->assertFalse($delivery->logDelivery());
        $this->assertFalse($delivery->canDeliver(OtpChannel::Email));

        $this->post(route('register'), [
            'name' => 'Nelson Kwoba',
            'email' => 'nelson@example.com',
            'phone' => '0705359472',
            'terms_accepted' => '1',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
            'role' => 'TRAVELER',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'nelson@example.com']);
    }

    public function test_provider_registration_requires_and_saves_business_details(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => 'Operator Owner',
            'email' => 'operator@example.com',
            'phone' => '0712345678',
            'terms_accepted' => '1',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
            'role' => 'OPERATOR',
        ])->assertSessionHasErrors('business_name');

        foreach ([
            ['role' => 'OPERATOR', 'email' => 'operator@example.com', 'phone' => '0712345678', 'name' => 'Savanna Tours'],
            ['role' => 'VEHICLE_OWNER', 'email' => 'owner@example.com', 'phone' => '0712345679', 'name' => 'Highland Rides'],
        ] as $provider) {
            $this->post(route('register'), [
                'name' => 'Account Owner',
                'email' => $provider['email'],
                'phone' => $provider['phone'],
                'terms_accepted' => '1',
                'password' => 'Jungle@Trail29',
                'password_confirmation' => 'Jungle@Trail29',
                'role' => $provider['role'],
                'business_name' => $provider['name'],
                'business_description' => 'Locally operated journeys and transport.',
            ])->assertRedirect();

            $user = User::query()->where('email', $provider['email'])->firstOrFail();
            $profile = $provider['role'] === 'OPERATOR' ? $user->operatorProfile : $user->vehicleOwnerProfile;
            $nameColumn = $provider['role'] === 'OPERATOR' ? 'company_name' : 'business_name';

            $this->assertSame($provider['name'], $profile->{$nameColumn});
            $this->assertSame('Locally operated journeys and transport.', $profile->description);
        }
    }

    public function test_phone_verification_is_not_required_or_available(): void
    {
        $verification = app(AccountVerificationService::class);
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_verified_at' => null,
        ]);

        $this->assertSame([OtpChannel::Email], $verification->requiredChannels());
        $this->assertTrue($verification->isFullyVerified($user));

        $this->post(route('verification.verify'), [
            'user_id' => $user->id,
            'channel' => OtpChannel::Phone->value,
            'code' => '123456',
        ])->assertSessionHasErrors('channel');

        $this->post(route('verification.resend'), [
            'user_id' => $user->id,
            'channel' => OtpChannel::Phone->value,
        ])->assertSessionHasErrors('channel');
    }

    public function test_registration_rejects_passwords_that_break_policy_or_match_account_identifiers(): void
    {
        $cases = [
            ['Ab1#xy', 'Password must be at least 8 characters.'],
            ['lowercase1#', 'Password must contain at least one uppercase letter.'],
            ['UPPERCASE1#', 'Password must contain at least one lowercase letter.'],
            ['Password#', 'Password must contain at least one number.'],
            ['Password1', 'Password must contain at least one special character.'],
            ['Password123!', 'Password must not be a commonly used password.'],
            ['P@ssw0rd', 'Password must not be a commonly used password.'],
            ['Amina#2026', 'Password must not contain your name, email address or phone number.'],
        ];

        foreach ($cases as [$password, $message]) {
            $this->from(route('register'))->post(route('register'), [
                'name' => 'Amina Kariuki',
                'email' => 'amina@example.com',
                'phone' => '0712345678',
                'terms_accepted' => '1',
                'password' => $password,
                'password_confirmation' => $password,
                'role' => 'TRAVELER',
            ])->assertSessionHasErrors(['password' => $message]);
        }
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => 'Amina Kariuki',
            'email' => 'amina@example.com',
            'phone' => '0712345678',
            'terms_accepted' => '1',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Different@Trail29',
            'role' => 'TRAVELER',
        ])->assertSessionHasErrors(['password' => 'Password confirmation does not match.']);
    }

    public function test_registration_rejects_passwords_reported_as_breached(): void
    {
        // The setUp catch-all fake would shadow a second Http::fake here, so the
        // breached suffix list is seeded into the cache instead; the rule then
        // rejects without making a lookup request.
        $password = 'Jungle@Trail29';
        $digest = strtoupper(sha1($password));
        Cache::put('password.breach.suffixes.'.substr($digest, 0, 5), [substr($digest, 5)], now()->addMinutes(5));

        $this->from(route('register'))->post(route('register'), [
            'name' => 'Amina Kariuki',
            'email' => 'amina@example.com',
            'phone' => '0712345678',
            'terms_accepted' => '1',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'TRAVELER',
        ])->assertSessionHasErrors(['password' => 'This password has appeared in a data breach. Please choose a different one.']);
    }

    public function test_password_change_requires_current_password_and_updates_to_a_hashed_value(): void
    {
        $user = User::factory()->create(['name' => 'Amina Kariuki', 'email' => 'amina@example.com', 'password' => 'OldPassword#29']);

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'OldPassword#29',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Jungle@Trail29', $user->refresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password_and_account_identifiers(): void
    {
        $user = User::factory()->create(['name' => 'Amina Kariuki', 'email' => 'amina@example.com', 'password' => 'OldPassword#29']);

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'incorrect',
            'password' => 'Amina#2026',
            'password_confirmation' => 'Amina#2026',
        ])->assertSessionHasErrors(['current_password', 'password']);
    }

    public function test_password_reset_accepts_a_strong_password_and_hashes_it(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword#29']);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Jungle@Trail29', $user->refresh()->password));
    }

    public function test_password_reset_request_sends_email_through_the_configured_resend_transport(): void
    {
        config([
            'services.otp.delivery' => 'auto',
            'services.otp.email_transport' => 'resend',
            'services.resend.key' => 'test-resend-key',
            'services.resend.from' => 'Havenedge Tourlink <reset@example.com>',
        ]);
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.resend.com/emails'
                && $payload['to'] === ['reset@example.com']
                && $payload['subject'] === 'Reset your '.config('app.name').' password'
                && Str::contains($payload['html'], '/reset-password/')
                && Str::contains($payload['html'], 'email=reset%40example.com');
        });
    }

    public function test_password_reset_delivery_fails_explicitly_when_no_transport_is_configured(): void
    {
        config([
            'app.env' => 'production',
            'services.otp.email_transport' => 'auto',
            'services.resend.key' => null,
            'services.resend.from' => null,
            'mail.default' => 'log',
        ]);

        $this->expectException(RuntimeException::class);

        app(OtpDeliveryService::class)
            ->sendPasswordResetLink(new User(['name' => 'Test User', 'email' => 'test@example.com']), 'test-token');
    }

    public function test_password_reset_rejects_an_invalid_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword#29']);
        $token = Password::createToken($user);

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'Password1',
                'password_confirmation' => 'Password1',
            ])->assertSessionHasErrors(['password' => 'Password must contain at least one special character.']);

        $this->assertTrue(Hash::check('OldPassword#29', $user->refresh()->password));
    }

    public function test_password_reset_requires_matching_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword#29']);
        $token = Password::createToken($user);

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'Jungle@Trail29',
                'password_confirmation' => 'Different@Trail29',
            ])->assertSessionHasErrors(['password' => 'Password confirmation does not match.']);
    }
}
