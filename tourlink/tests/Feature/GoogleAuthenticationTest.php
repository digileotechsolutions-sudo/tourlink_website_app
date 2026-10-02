<?php

namespace Tests\Feature;

use App\Models\User;
use App\Role;
use App\Services\Auth\GoogleIdTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-web-client-id',
            'services.otp.delivery' => 'log',
        ]);
        $this->setReferralSettings();
        Http::fake(['*' => Http::response('', 200)]);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_google_sign_up_creates_a_pending_user_after_phone_and_role_completion(): void
    {
        $nonce = $this->googleNonce('register');
        $identity = $this->identity($nonce, [
            'sub' => 'google-sub-new-user',
            'email' => 'new.google@example.com',
            'name' => 'New Google Traveller',
            'picture' => 'https://lh3.googleusercontent.com/avatar.png',
        ]);

        $this->mock(GoogleIdTokenVerifier::class)
            ->shouldReceive('verify')
            ->once()
            ->with('signed-google-id-token', 'google-web-client-id')
            ->andReturn($identity);

        $this->post(route('google.authenticate'), [
            'credential' => 'signed-google-id-token',
            'mode' => 'register',
            'role' => Role::Operator->value,
        ])->assertRedirect(route('google.complete'));

        $this->get(route('google.complete'))
            ->assertOk()
            ->assertViewHas('initialRole', Role::Operator->value);

        $this->post(route('google.complete'), [
            'phone' => '0712345678',
            'role' => Role::Operator->value,
            'business_name' => 'Savanna Google Tours',
            'business_description' => 'Small-group wildlife and culture trips.',
        ])->assertRedirect();

        $user = User::query()->where('google_id', 'google-sub-new-user')->firstOrFail();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('new.google@example.com', $user->email);
        $this->assertSame('254712345678', $user->phone);
        $this->assertSame(Role::Operator, $user->role);
        $this->assertSame('google', $user->auth_provider);
        $this->assertSame('https://lh3.googleusercontent.com/avatar.png', $user->avatar_url);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('PENDING', $user->approval_status->value);
        $this->assertSame('Savanna Google Tours', $user->operatorProfile()->value('company_name'));
        $this->assertSame('Small-group wildlife and culture trips.', $user->operatorProfile()->value('description'));
    }

    public function test_google_sign_in_links_an_existing_verified_email_without_creating_a_duplicate(): void
    {
        $user = User::factory()->create([
            'name' => 'Existing Traveller',
            'email' => 'existing.google@example.com',
            'google_id' => null,
            'auth_provider' => 'password',
        ]);
        $nonce = $this->googleNonce('login');
        $identity = $this->identity($nonce, [
            'sub' => 'google-sub-existing-user',
            'email' => 'existing.google@example.com',
            'name' => 'Existing Traveller',
        ]);

        $this->mock(GoogleIdTokenVerifier::class)
            ->shouldReceive('verify')
            ->once()
            ->with('signed-google-id-token', 'google-web-client-id')
            ->andReturn($identity);

        $this->post(route('google.authenticate'), [
            'credential' => 'signed-google-id-token',
            'mode' => 'login',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('google-sub-existing-user', $user->refresh()->google_id);
        $this->assertSame('google', $user->auth_provider);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_sign_in_rejects_a_nonce_not_issued_for_the_session(): void
    {
        $this->googleNonce('login');
        $identity = $this->identity('nonce-from-another-session');

        $this->mock(GoogleIdTokenVerifier::class)
            ->shouldReceive('verify')
            ->once()
            ->andReturn($identity);

        $this->post(route('google.authenticate'), [
            'credential' => 'signed-google-id-token',
            'mode' => 'login',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertDatabaseCount('users', 0);
        $this->assertFalse(Auth::check());
    }

    public function test_google_id_token_verifier_checks_signature_and_required_claims(): void
    {
        Cache::forget('google.oidc.signing_keys');
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($privateKey);
        $details = openssl_pkey_get_details($privateKey);
        $this->assertIsArray($details);

        $jwk = [
            'kty' => 'RSA',
            'kid' => 'test-signing-key',
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];
        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response(
                ['keys' => [$jwk]],
                200,
                ['Cache-Control' => 'public, max-age=3600'],
            ),
        ]);

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'kid' => 'test-signing-key'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-web-client-id',
            'exp' => now()->addMinutes(5)->timestamp,
            'iat' => now()->timestamp,
            'sub' => 'google-test-subject',
            'email' => 'verified@example.com',
            'email_verified' => true,
            'name' => 'Verified User',
            'nonce' => 'session-nonce',
        ], JSON_THROW_ON_ERROR));
        $signedContent = $header.'.'.$claims;
        $this->assertTrue(openssl_sign($signedContent, $signature, $privateKey, OPENSSL_ALGO_SHA256));
        $token = $signedContent.'.'.$this->base64UrlEncode($signature);

        $identity = app(GoogleIdTokenVerifier::class)->verify($token, 'google-web-client-id');

        $this->assertSame('google-test-subject', $identity['sub']);
        $this->assertSame('verified@example.com', $identity['email']);
        $this->assertSame('Verified User', $identity['name']);
        $this->assertSame('session-nonce', $identity['nonce']);
    }

    public function test_google_id_token_verifier_rejects_malformed_credentials(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(GoogleIdTokenVerifier::class)->verify('not-a-jwt', 'google-web-client-id');
    }

    private function googleNonce(string $routeName): string
    {
        $response = $this->get(route($routeName));
        $response->assertOk();

        preg_match('/data-google-nonce="([^"]+)"/', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        return $matches[1];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @return array{sub: string, email: string, name: string, picture: ?string, nonce: string} */
    private function identity(string $nonce, array $overrides = []): array
    {
        return array_merge([
            'sub' => 'google-sub-default',
            'email' => 'google@example.com',
            'name' => 'Google User',
            'picture' => null,
            'nonce' => $nonce,
        ], $overrides, ['nonce' => $nonce]);
    }
}
