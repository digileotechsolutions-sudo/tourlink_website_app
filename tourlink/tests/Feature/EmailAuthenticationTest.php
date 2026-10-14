<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireTermsAcceptance;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetMail;
use App\Mail\VerificationCodeMail;
use App\Models\OtpChallenge;
use App\Models\User;
use App\OtpChannel;
use App\Role;
use App\Services\Verification\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.env' => 'local',
            'services.otp.delivery' => 'auto',
            'services.otp.email_transport' => 'smtp',
            'services.otp.dev_mode' => true,
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.username' => 'mailer@example.test',
            'mail.mailers.smtp.password' => 'test-password',
        ]);
        $this->setReferralSettings();
        $this->withCookie(RequireTermsAcceptance::COOKIE_NAME, RequireTermsAcceptance::tokenFor());
        Mail::fake();
    }

    public function test_registration_sends_a_branded_email_otp_and_stores_only_a_keyed_hash(): void
    {
        $this->post(route('register'), [
            'name' => 'Amina Kariuki',
            'email' => 'amina@example.com',
            'phone' => '0712345678',
            'terms_accepted' => '1',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
            'role' => 'TRAVELER',
        ]);

        $user = User::query()->where('email', 'amina@example.com')->firstOrFail();
        $challenge = OtpChallenge::query()->where('user_id', $user->id)->firstOrFail();
        $code = session('dev_otp_email');

        $this->assertNull($user->email_verified_at);
        $this->assertIsString($code);
        $this->assertSame(64, strlen($challenge->code_hash));
        $this->assertNotSame($code, $challenge->code_hash);
        $this->assertSame(hash_hmac('sha256', $code, (string) config('app.key')), $challenge->code_hash);
        $this->assertTrue($challenge->expires_at->between(now()->addMinutes(9), now()->addMinutes(10)));

        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use ($user, $code): bool {
            return $mail->hasTo($user->email)
                && $mail->registration
                && Str::contains($mail->render(), 'Your Havenedge Tourlink verification code is: '.$code)
                && Str::contains($mail->render(), 'expires in 10 minutes');
        });
    }

    public function test_valid_email_otp_is_one_time_and_marks_email_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(OtpService::class)->issue($user, OtpChannel::Email);

        $this->assertSame('verified', app(OtpService::class)->verify($user, OtpChannel::Email, $code));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('missing', app(OtpService::class)->verify($user, OtpChannel::Email, $code));
    }

    public function test_invalid_otp_attempts_are_limited_and_resend_replaces_the_old_code(): void
    {
        $user = User::factory()->unverified()->create();
        $otp = app(OtpService::class);
        $oldCode = $otp->issue($user, OtpChannel::Email);
        $incorrectCode = str_pad((string) (((int) $oldCode + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->assertSame('invalid', $otp->verify($user, OtpChannel::Email, $incorrectCode));
        }
        $this->assertSame('locked', $otp->verify($user, OtpChannel::Email, $incorrectCode));

        $newCode = $otp->issue($user, OtpChannel::Email);
        $this->assertNotSame($oldCode, $newCode);
        $this->assertSame('missing', $otp->verify($user, OtpChannel::Email, $oldCode));
        $this->assertSame('verified', $otp->verify($user, OtpChannel::Email, $newCode));
    }

    public function test_expired_email_otp_cannot_be_used(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(OtpService::class)->issue($user, OtpChannel::Email);
        $this->travel(11)->minutes();

        $this->assertSame('expired', app(OtpService::class)->verify($user, OtpChannel::Email, $code));
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_endpoint_sends_a_new_code_and_applies_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        $payload = ['user_id' => $user->id, 'channel' => OtpChannel::Email->value];

        $this->post(route('verification.resend'), $payload)->assertRedirect();
        Mail::assertSent(VerificationCodeMail::class, 1);
        $this->post(route('verification.resend'), $payload)->assertTooManyRequests();
    }

    public function test_verification_page_shows_the_code_expiry_countdown(): void
    {
        $user = User::factory()->unverified()->create();
        app(OtpService::class)->issue($user, OtpChannel::Email);

        $this->get(route('verification.notice', ['user' => $user->id]))
            ->assertOk()
            ->assertSee('data-otp-expires-at=', false)
            ->assertSeeText('Each code expires after ten minutes.');
    }

    public function test_forgot_password_is_generic_and_sends_branded_reset_mail_for_registered_accounts(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'If that email is registered, password reset instructions have been sent.');

        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($user): bool {
            return $mail->hasTo($user->email)
                && Str::contains($mail->render(), 'Someone requested a password reset for your Havenedge Tourlink account.')
                && Str::contains($mail->render(), 'Reset Password')
                && Str::contains($mail->render(), 'expires in 60 minutes');
        });

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'If that email is registered, password reset instructions have been sent.');

        Mail::fake();
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'If that email is registered, password reset instructions have been sent.');
        Mail::assertNothingOutgoing();
    }

    public function test_emailed_password_reset_link_opens_form_and_completes_password_reset(): void
    {
        $user = User::factory()->create(['email' => 'reset-link@example.com']);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $apiToken = $user->createToken('before-email-password-reset');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($user, $admin, $apiToken): bool {
            $path = (string) parse_url($mail->resetUrl, PHP_URL_PATH);
            $query = [];
            parse_str((string) parse_url($mail->resetUrl, PHP_URL_QUERY), $query);
            $token = basename($path);

            $this->assertSame($user->email, $query['email'] ?? null);
            $this->actingAs($admin)
                ->get($mail->resetUrl)
                ->assertOk()
                ->assertSee('name="token" value="'.$token.'"', false)
                ->assertSee('name="email"');

            $this->post(route('password.update'), [
                'token' => $token,
                'email' => $query['email'],
                'password' => 'Jungle@Trail29',
                'password_confirmation' => 'Jungle@Trail29',
            ])->assertRedirect(route('login'))
                ->assertSessionHas('status', 'Your password was successfully changed. A confirmation email has been sent.');

            $this->assertTrue(Hash::check('Jungle@Trail29', $user->fresh()->password));
            $this->assertDatabaseMissing('personal_access_tokens', ['id' => $apiToken->accessToken->id]);

            return $mail->hasTo($user->email);
        });

        Mail::assertSent(PasswordChangedMail::class, fn (PasswordChangedMail $mail): bool => $mail->hasTo($user->email)
            && str_contains($mail->render(), 'Your Havenedge Tourlink account password was successfully changed.'));
    }

    public function test_password_change_displays_success_and_emails_account_notice(): void
    {
        $user = User::factory()->create(['password' => 'Current@Trail29']);

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'Current@Trail29',
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
        ])->assertRedirect()
            ->assertSessionHas('status', 'Your password was successfully changed. A confirmation email has been sent.');

        Mail::assertSent(PasswordChangedMail::class, fn (PasswordChangedMail $mail): bool => $mail->hasTo($user->email)
            && str_contains($mail->render(), 'Your Havenedge Tourlink account password was successfully changed.'));
    }

    public function test_authentication_forms_are_not_cached(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('name="_token"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->assertSee('name="_token"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->assertSee('name="_token"', false);

        $this->get(route('password.reset', ['token' => 'reset-token', 'email' => 'reset@example.com']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->assertSee('name="referrer" content="no-referrer"', false);

        $userAwaitingVerification = User::factory()->unverified()->create();
        $this->get(route('verification.notice', $userAwaitingVerification))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->assertSee('name="_token"', false);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function test_password_login_works_without_the_app_session_registry(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('account_sessions'));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_api_logout_revokes_the_current_token_without_the_session_registry(): void
    {
        $this->assertSame('string', Schema::getColumnType('personal_access_tokens', 'tokenable_id'));

        $user = User::factory()->create();
        $token = $user->createToken('logout-test');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('account_sessions'));
    }

    public function test_valid_password_reset_hashes_password_invalidates_token_and_allows_login(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $apiToken = $user->createToken('before-password-reset');
        $user->forceFill(['remember_token' => 'previous-remember-token'])->save();
        $token = Password::createToken($user);
        $newPassword = 'Jungle@Trail29';

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertNotSame('previous-remember-token', $user->fresh()->remember_token);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $apiToken->accessToken->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertSessionHasErrors('email');

        $this->post(route('login'), ['email' => $user->email, 'password' => $newPassword])
            ->assertRedirect(route('dashboard'));
    }

    public function test_invalid_and_expired_password_reset_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->post(route('password.update'), [
            'token' => 'not-a-valid-token',
            'email' => $user->email,
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
        ])->assertSessionHasErrors('email');

        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
        ])->assertSessionHasErrors('email');
    }
}
