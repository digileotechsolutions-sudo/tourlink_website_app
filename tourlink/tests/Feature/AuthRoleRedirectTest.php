<?php

namespace Tests\Feature;

use App\AccountApprovalStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_ignores_a_stale_traveler_dashboard_destination(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('login'), [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_traveler_login_preserves_a_traveler_destination(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $destination = route('traveler.favorites');

        $this->withSession(['url.intended' => $destination])
            ->post(route('login'), [
                'email' => $traveler->email,
                'password' => 'password',
            ])
            ->assertRedirect($destination);
    }

    public function test_traveler_login_redirects_to_support_chat_when_that_was_the_intended_destination(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $destination = route('support.index');

        $this->withSession(['url.intended' => $destination])
            ->post(route('login'), [
                'email' => $traveler->email,
                'password' => 'password',
            ])
            ->assertRedirect($destination);

        $this->get($destination)
            ->assertOk()
            ->assertSeeText('Type your message');
    }

    public function test_support_signup_keeps_chat_destination_through_verification_and_admin_approval_wait(): void
    {
        $user = User::factory()->create([
            'role' => 'TRAVELER',
            'approval_status' => AccountApprovalStatus::Pending,
        ]);

        $this->get(route('verification.notice', [
            'user' => $user->id,
            'continue' => 'support',
        ]))
            ->assertOk()
            ->assertSeeText('Your email is verified. Please wait for an administrator to approve your account.')
            ->assertSee(route('login', ['continue' => 'support']), false);
    }

    public function test_unapproved_support_traveler_login_keeps_chat_destination_for_later(): void
    {
        $traveler = User::factory()->create([
            'role' => 'TRAVELER',
            'approval_status' => AccountApprovalStatus::Pending,
        ]);

        $this->post(route('login', ['continue' => 'support']), [
            'email' => $traveler->email,
            'password' => 'password',
            'continue' => 'support',
        ])
            ->assertRedirect(route('login', ['continue' => 'support']))
            ->assertSessionHasErrors('email');

        $traveler->forceFill(['approval_status' => AccountApprovalStatus::Approved])->save();

        $this->post(route('login', ['continue' => 'support']), [
            'email' => $traveler->email,
            'password' => 'password',
            'continue' => 'support',
        ])->assertRedirect(route('support.index'));
    }
}