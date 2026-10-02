<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireTermsAcceptance;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class RequireTermsAcceptanceTest extends TestCase
{
    public function test_public_browsing_and_login_remain_available_without_acceptance(): void
    {
        $this->get(route('login'))
            ->assertOk();

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('35. Acceptance')
            ->assertSee('Print / save as PDF');

        $this->get(route('about'))->assertOk();

        $this->get(route('register'))
            ->assertRedirect(route('terms'));

        $this->post(route('google.authenticate'), [
            'mode' => 'register',
            'role' => 'TRAVELER',
        ])->assertRedirect(route('terms'));
    }

    public function test_google_sign_in_does_not_require_terms_acceptance(): void
    {
        $this->post(route('google.authenticate'), ['mode' => 'login'])
            ->assertRedirect(route('login'));
    }

    public function test_acceptance_returns_to_the_requested_page_and_is_required_again_for_a_new_version(): void
    {
        $registrationUrl = route('register', ['role' => 'OPERATOR']);
        $this->get($registrationUrl)->assertRedirect(route('terms'));

        $response = $this->post(route('terms.accept'), ['accepted' => '1'])
            ->assertRedirect($registrationUrl)
            ->assertCookie(RequireTermsAcceptance::COOKIE_NAME);

        $acceptanceCookie = collect($response->headers->getCookies())->first(
            fn (Cookie $cookie): bool => $cookie->getName() === RequireTermsAcceptance::COOKIE_NAME,
        );

        $this->assertNotNull($acceptanceCookie);

        $this->withCookie(RequireTermsAcceptance::COOKIE_NAME, $acceptanceCookie->getValue())
            ->get($registrationUrl)
            ->assertOk();

        config(['app.terms_version' => '2026-10-03']);

        $this->withCookie(RequireTermsAcceptance::COOKIE_NAME, $acceptanceCookie->getValue())
            ->get($registrationUrl)
            ->assertRedirect(route('terms'));
    }

    public function test_acceptance_checkbox_is_required(): void
    {
        $this->post(route('terms.accept'), [])
            ->assertSessionHasErrors('accepted');
    }
}