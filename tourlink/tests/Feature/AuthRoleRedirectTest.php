<?php

namespace Tests\Feature;

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
}