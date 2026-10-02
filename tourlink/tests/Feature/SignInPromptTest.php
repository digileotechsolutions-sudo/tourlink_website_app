<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignInPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_out_homepage_includes_the_sign_in_prompt(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-sign-in-prompt', false)
            ->assertSee('Keep your journeys together')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false)
            ->assertSee('data-sign-in-prompt-close', false);
    }

    public function test_signed_in_homepage_does_not_include_the_sign_in_prompt(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-sign-in-prompt', false);
    }

    public function test_sign_in_prompt_is_only_rendered_on_homepage(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertDontSee('data-sign-in-prompt', false);
    }
}
