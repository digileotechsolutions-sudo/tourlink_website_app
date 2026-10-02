<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireTermsAcceptance;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class RequireTermsAcceptanceTest extends TestCase
{
    public function test_unaccepted_requests_are_sent_to_the_terms_page(): void
    {
        $this->get(route('login'))
            ->assertRedirect(route('terms'));

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('35. Acceptance')
            ->assertSee('Print / save as PDF');
    }

    public function test_acceptance_returns_to_the_requested_page_and_is_required_again_for_a_new_version(): void
    {
        $this->get(route('login'))->assertRedirect(route('terms'));

        $response = $this->post(route('terms.accept'), ['accepted' => '1'])
            ->assertRedirect(route('login'))
            ->assertCookie(RequireTermsAcceptance::COOKIE_NAME);

        $acceptanceCookie = collect($response->headers->getCookies())->first(
            fn (Cookie $cookie): bool => $cookie->getName() === RequireTermsAcceptance::COOKIE_NAME,
        );

        $this->assertNotNull($acceptanceCookie);

        $this->withCookie(RequireTermsAcceptance::COOKIE_NAME, $acceptanceCookie->getValue())
            ->get(route('login'))
            ->assertOk();

        config(['app.terms_version' => '2026-10-03']);

        $this->withCookie(RequireTermsAcceptance::COOKIE_NAME, $acceptanceCookie->getValue())
            ->get(route('login'))
            ->assertRedirect(route('terms'));
    }

    public function test_acceptance_checkbox_is_required(): void
    {
        $this->post(route('terms.accept'), [])
            ->assertSessionHasErrors('accepted');
    }
}