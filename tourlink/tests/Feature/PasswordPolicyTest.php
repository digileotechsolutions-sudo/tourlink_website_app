<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
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
            'password' => 'Jungle@Trail29',
            'password_confirmation' => 'Jungle@Trail29',
            'role' => 'TRAVELER',
        ]);

        $user = User::query()->firstOrFail();
        $response->assertRedirect(route('verification.notice', ['user' => $user->id]));
        $this->assertTrue(Hash::check('Jungle@Trail29', $user->password));
        $this->assertNotSame('Jungle@Trail29', $user->password);
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
