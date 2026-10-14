<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeHeroVideoTest extends TestCase
{
    public function test_home_hero_video_is_served_from_the_laravel_public_assets(): void
    {
        $this->get(route('public.home-hero-video'))
            ->assertOk()
            ->assertHeader('content-type', 'video/mp4');
    }
}
