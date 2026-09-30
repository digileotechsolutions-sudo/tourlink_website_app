<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaOfflineActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_catalog_pages_are_marked_safe_for_public_caching(): void
    {
        $this->get(route('trips.index'))
            ->assertOk()
            ->assertHeader('X-PWA-Cacheable', 'public')
            // Symfony sorts Cache-Control directives alphabetically when the
            // response is prepared, so the directives are asserted in that
            // canonical order rather than the order the middleware set them.
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, public');
    }

    public function test_authenticated_pages_are_not_marked_for_public_caching(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.change'))->assertOk();

        $this->assertNull($response->headers->get('X-PWA-Cacheable'));
    }

    public function test_csrf_bootstrap_is_private_and_never_cached(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('pwa.csrf'))
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_replayed_favorite_save_requests_create_only_one_favorite(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create();
        $payload = [
            'trip_id' => $trip->id,
            'expected_user_id' => $user->id,
        ];

        $this->actingAs($user)->postJson(route('traveler.favorites.save', $trip), $payload)->assertOk()->assertJsonPath('saved', true);
        $this->actingAs($user)->postJson(route('traveler.favorites.save', $trip), $payload)->assertOk()->assertJsonPath('saved', true);

        $this->assertSame(1, Favorite::query()->where('user_id', $user->id)->where('trip_id', $trip->id)->count());
    }

    public function test_favorite_sync_cannot_write_for_a_different_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $trip = Trip::factory()->create();

        $this->actingAs($user)->postJson(route('traveler.favorites.save', $trip), [
            'trip_id' => $trip->id,
            'expected_user_id' => $otherUser->id,
        ])->assertForbidden();

        $this->assertSame(0, Favorite::query()->count());
    }
}
