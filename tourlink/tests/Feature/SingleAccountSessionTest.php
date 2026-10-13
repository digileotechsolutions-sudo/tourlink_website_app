<?php

namespace Tests\Feature;

use App\Models\AccountSession;
use App\Models\User;
use App\Services\Auth\AccountSessionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SingleAccountSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setReferralSettings();
    }

    public function test_successful_password_login_registers_the_rotated_web_session(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $session = AccountSession::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame('web', $session->session_type);
        $this->assertSame(hash('sha256', 'web:'.session()->getId()), $session->session_fingerprint);
        $this->assertGreaterThan(now()->timestamp, $session->expires_at->timestamp);
    }

    public function test_web_session_validation_uses_its_registered_fingerprint_after_session_id_rotation(): void
    {
        $user = User::factory()->create();
        $fingerprint = hash('sha256', 'web:registered-session-id');
        AccountSession::query()->create([
            'user_id' => $user->id,
            'session_fingerprint' => $fingerprint,
            'session_type' => 'web',
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $session = app('session')->driver();
        $session->setId('rotated-session-id');
        $session->put('_account_session_fingerprint', $fingerprint);
        $request = Request::create('/dashboard');
        $request->setLaravelSession($session);

        $this->assertTrue(app(AccountSessionService::class)->validateWebSession($user, $request));
    }

    public function test_session_storage_failure_returns_a_login_error_instead_of_leaving_login_pending(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $sessions = \Mockery::mock(AccountSessionService::class)->makePartial();
        $sessions->shouldReceive('registerWebLogin')
            ->once()
            ->andThrow(new QueryException('mysql', 'insert into account_sessions', [], new \PDOException('table missing')));
        $sessions->shouldReceive('discardUnregisteredWebLogin')->once();
        $this->app->instance(AccountSessionService::class, $sessions);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Sign-in is temporarily unavailable because session security could not be initialized. Please contact support.',
            ]);
    }

    public function test_new_web_login_replaces_the_previous_active_session(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'remember_token' => 'old-remember-token',
        ]);
        $activeFingerprint = hash('sha256', 'web:existing-device-session');
        AccountSession::query()->create([
            'user_id' => $user->id,
            'session_fingerprint' => $activeFingerprint,
            'session_type' => 'web',
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $newFingerprint = AccountSession::query()->where('user_id', $user->id)->value('session_fingerprint');
        $this->assertNotSame($activeFingerprint, $newFingerprint);
        $this->assertDatabaseHas('account_sessions', [
            'user_id' => $user->id,
            'session_fingerprint' => $newFingerprint,
            'session_type' => 'web',
        ]);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'remember_token' => 'old-remember-token',
        ]);
    }

    public function test_expired_session_can_be_replaced_by_a_new_login(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        AccountSession::query()->create([
            'user_id' => $user->id,
            'session_fingerprint' => hash('sha256', 'web:expired-device-session'),
            'session_type' => 'web',
            'last_activity_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinute(),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotSame(
            hash('sha256', 'web:expired-device-session'),
            AccountSession::query()->where('user_id', $user->id)->value('session_fingerprint'),
        );
    }

    public function test_logout_revokes_the_session_and_allows_a_new_login(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $credentials = ['email' => $user->email, 'password' => 'password'];

        $this->post(route('login'), $credentials)->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('account_sessions', ['user_id' => $user->id]);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertDatabaseMissing('account_sessions', ['user_id' => $user->id]);

        $this->post(route('login'), $credentials)->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('account_sessions', ['user_id' => $user->id]);
    }

    public function test_new_sanctum_token_replaces_and_revokes_the_previous_api_session(): void
    {
        $user = User::factory()->create();
        $firstToken = $user->createToken('mobile-one');
        $secondToken = $user->createToken('mobile-two');

        $this->withHeader('Authorization', 'Bearer '.$firstToken->plainTextToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->assertDatabaseHas('account_sessions', [
            'user_id' => $user->id,
            'personal_access_token_id' => $firstToken->accessToken->id,
            'session_type' => 'token',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$secondToken->plainTextToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $firstToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $secondToken->accessToken->id]);
        $this->assertDatabaseHas('account_sessions', [
            'user_id' => $user->id,
            'personal_access_token_id' => $secondToken->accessToken->id,
            'session_type' => 'token',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$firstToken->plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_api_logout_revokes_its_token_and_active_session(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/user')
            ->assertOk();

        $this->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('account_sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_administrator_can_terminate_an_active_account_session(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $user = User::factory()->create();
        AccountSession::query()->create([
            'user_id' => $user->id,
            'session_fingerprint' => hash('sha256', 'web:revoked-session'),
            'session_type' => 'web',
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertSee($user->email);

        $this->delete(route('admin.sessions.destroy', $user))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('account_sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action' => 'user.session_terminated',
            'entity_id' => $user->id,
        ]);
    }
}
