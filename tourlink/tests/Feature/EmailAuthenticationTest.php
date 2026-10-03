<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireTermsAcceptance;
use App\Mail\PasswordResetMail;
use App\Mail\VerificationCodeMail;
use App\Models\OtpChallenge;
use App\Models\User;
use App\OtpChannel;
use App\Services\Verification\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
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

    public function test_valid_password_reset_hashes_password_invalidates_token_and_allows_login(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::createToken($user);
        $newPassword = 'Jungle@Trail29';

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
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
