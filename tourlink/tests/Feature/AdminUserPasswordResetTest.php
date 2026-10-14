<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminUserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_email_any_user_a_password_reset_link_and_the_action_is_audited(): void
    {
        config([
            'services.otp.delivery' => 'auto',
            'services.otp.email_transport' => 'resend',
            'services.resend.key' => 'test-resend-key',
            'services.resend.from' => 'Havenedge Tourlink <reset@example.com>',
        ]);
        Http::fake(['*' => Http::response('', 200)]);

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $user = User::factory()->create(['role' => 'OPERATOR', 'email' => 'operator@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.users.password-reset', $user))
            ->assertRedirect()
            ->assertSessionHas('status', 'A password reset link was sent to operator@example.com.');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.resend.com/emails'
            && $request->data()['to'] === ['operator@example.com']
            && str_contains($request->data()['html'], '/reset-password/'));

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'operator@example.com']);
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action' => 'user.password_reset_link_sent',
            'entity' => 'User',
            'entity_id' => $user->id,
        ]);
    }

    public function test_non_admin_cannot_request_another_users_password_reset_link(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $user = User::factory()->create(['role' => 'OPERATOR']);

        $this->actingAs($traveler)
            ->post(route('admin.users.password-reset', $user))
            ->assertForbidden();

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('admin_logs', [
            'action' => 'user.password_reset_link_sent',
            'entity_id' => $user->id,
        ]);
    }
}
