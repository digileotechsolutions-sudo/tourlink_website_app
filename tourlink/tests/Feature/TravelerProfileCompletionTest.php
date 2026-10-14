<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelerProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_the_traveler_profile_shows_success_and_removes_the_reminder(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->from(route('traveler.profile'))
            ->put(route('traveler.profile.update'), [
                'name' => $user->name,
                'phone' => $user->phone,
                'bio' => 'I love discovering new places and meeting local guides.',
                'preferred_currency' => 'KES',
            ])
            ->assertOk()
            ->assertSeeText('Profile completed successfully.')
            ->assertDontSeeText('Complete your profile');
    }
}
