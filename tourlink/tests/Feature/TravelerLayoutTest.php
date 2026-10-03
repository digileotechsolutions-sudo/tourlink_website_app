<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelerLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_traveler_dashboard_renders_the_portal_shell_and_page_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<body class="app-page">', false)
            ->assertSee('Plan your next journey')
            ->assertSee('portal-sidebar', false)
            ->assertSee('portal-footer', false);
    }
}
