<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\AccountSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSessionReplacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_login_invalidates_only_the_same_users_previous_session(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $sessionKey = AccountSessionService::SESSION_VERSION_KEY;

        $this->get(route('login'))->assertOk();
        $sessionIdBeforeLogin = session()->getId();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotSame($sessionIdBeforeLogin, session()->getId());
        $deviceASessionVersion = session($sessionKey);
        $this->assertSame(2, $deviceASessionVersion);
        $this->get(route('dashboard'))->assertOk();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $deviceBSessionVersion = session($sessionKey);
        $this->assertSame(3, $deviceBSessionVersion);
        $this->get(route('dashboard'))->assertOk();

        $this->withSession([$sessionKey => $deviceASessionVersion])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($user)
            ->withSession([$sessionKey => $deviceBSessionVersion])
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($otherUser)
            ->withSession([
                $sessionKey => $otherUser->session_version,
                AccountSessionService::SESSION_STARTED_AT_KEY => now()->timestamp,
            ])
            ->get(route('dashboard'))
            ->assertOk();
        $this->assertSame(1, $otherUser->fresh()->session_version);
    }

    public function test_a_session_expires_at_its_absolute_lifetime(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->travel(12)->hours();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session_and_regenerates_the_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession([
            AccountSessionService::SESSION_VERSION_KEY => $user->session_version,
            AccountSessionService::SESSION_STARTED_AT_KEY => now()->timestamp,
        ]);
        $csrfToken = session()->token();

        $this->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNotSame($csrfToken, session()->token());
        $this->assertNull(session(AccountSessionService::SESSION_VERSION_KEY));
    }
}
