<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class GoogleIdTokenVerifier
{
    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const JWKS_CACHE_KEY = 'google.oidc.signing_keys';

    /**
     * @return array{sub: string, email: string, name: string, picture: ?string, nonce: string}
     */
    public function verify(string $token, string $clientId): array
    {
        if ($clientId === '') {
            throw new RuntimeException('Google sign-in is not configured.');
        }

        if ($token === '' || strlen($token) > 16384) {
            throw new InvalidArgumentException('Invalid Google credential.');
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid Google credential.');
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $parts;
        $header = $this->decodeJson($encodedHeader);
        $claims = $this->decodeJson($encodedClaims);

        if (($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null)) {
            throw new InvalidArgumentException('Unsupported Google credential.');
        }

        $key = $this->findKey($header['kid']);
        $publicKey = $this->publicKeyFromJwk($key);
        $signature = $this->decodeBase64Url($encodedSignature);

        if (openssl_verify($encodedHeader.'.'.$encodedClaims, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new InvalidArgumentException('Invalid Google credential signature.');
        }

        $now = time();
        $issuer = $claims['iss'] ?? null;
        $expiresAt = $claims['exp'] ?? null;
        $issuedAt = $claims['iat'] ?? null;
        $notBefore = $claims['nbf'] ?? null;
        $subject = $claims['sub'] ?? null;
        $email = $claims['email'] ?? null;
        $emailVerified = $claims['email_verified'] ?? false;
        $nonce = $claims['nonce'] ?? null;
        $audience = $claims['aud'] ?? null;
        $audiences = is_array($audience) ? $audience : [$audience];

        if (! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
            || ! in_array($clientId, $audiences, true)
            || (count($audiences) > 1 && ($claims['azp'] ?? null) !== $clientId)
            || ! is_numeric($expiresAt)
            || (int) $expiresAt <= $now
            || ! is_numeric($issuedAt)
            || (int) $issuedAt > $now + 60
            || (isset($notBefore) && (! is_numeric($notBefore) || (int) $notBefore > $now + 60))
            || ! is_string($subject)
            || $subject === ''
            || strlen($subject) > 255
            || ! is_string($email)
            || strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! in_array($emailVerified, [true, 'true'], true)
            || ! is_string($nonce)
            || $nonce === '') {
            throw new InvalidArgumentException('Google credential claims are invalid.');
        }

        $name = trim((string) ($claims['name'] ?? ''));
        if ($name === '') {
            $name = strstr($email, '@', true) ?: $email;
        }

        $picture = $claims['picture'] ?? null;
        if (! is_string($picture) || filter_var($picture, FILTER_VALIDATE_URL) === false || parse_url($picture, PHP_URL_SCHEME) !== 'https') {
            $picture = null;
        }

        return [
            'sub' => $subject,
            'email' => mb_strtolower(trim($email)),
            'name' => mb_substr($name, 0, 255),
            'picture' => $picture,
            'nonce' => $nonce,
        ];
    }

    /** @return array<string, mixed> */
    private function findKey(string $keyId): array
    {
        $keys = Cache::get(self::JWKS_CACHE_KEY);
        if (! is_array($keys)) {
            $keys = $this->fetchKeys();
        }

        foreach ($keys as $key) {
            if ($this->isSigningKey($key, $keyId)) {
                return $key;
            }
        }

        foreach ($this->fetchKeys() as $key) {
            if ($this->isSigningKey($key, $keyId)) {
                return $key;
            }
        }

        throw new InvalidArgumentException('Unknown Google signing key.');
    }

    private function isSigningKey(mixed $key, string $keyId): bool
    {
        return is_array($key)
            && ($key['kid'] ?? null) === $keyId
            && ($key['kty'] ?? null) === 'RSA'
            && in_array($key['use'] ?? 'sig', ['sig'], true)
            && in_array($key['alg'] ?? 'RS256', ['RS256'], true);
    }

    /** @return list<array<string, mixed>> */
    private function fetchKeys(): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->get(self::JWKS_URL);
        } catch (\Throwable) {
            throw new RuntimeException('Google signing keys are temporarily unavailable.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Google signing keys are temporarily unavailable.');
        }

        $keys = $response->json('keys');
        if (! is_array($keys) || $keys === []) {
            throw new RuntimeException('Google signing keys are temporarily unavailable.');
        }

        $cacheSeconds = 3600;
        if (preg_match('/max-age=(\d+)/i', (string) $response->header('Cache-Control'), $matches) === 1) {
            $cacheSeconds = max(60, min(86400, (int) $matches[1]));
        }

        Cache::put(self::JWKS_CACHE_KEY, $keys, now()->addSeconds($cacheSeconds));

        return $keys;
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $encoded): array
    {
        $decoded = $this->decodeBase64Url($encoded);
        $value = json_decode($decoded, true);

        if (! is_array($value)) {
            throw new InvalidArgumentException('Invalid Google credential.');
        }

        return $value;
    }

    private function decodeBase64Url(string $encoded): string
    {
        if ($encoded === '' || preg_match('/^[A-Za-z0-9_-]+$/', $encoded) !== 1) {
            throw new InvalidArgumentException('Invalid Google credential.');
        }

        $padded = strtr($encoded, '-_', '+/').str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $decoded = base64_decode($padded, true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid Google credential.');
        }

        return $decoded;
    }

    /** @param array<string, mixed> $jwk */
    private function publicKeyFromJwk(array $jwk): \OpenSSLAsymmetricKey
    {
        if (($jwk['kty'] ?? null) !== 'RSA' || ! is_string($jwk['n'] ?? null) || ! is_string($jwk['e'] ?? null)) {
            throw new InvalidArgumentException('Invalid Google signing key.');
        }

        $modulus = $this->decodeBase64Url($jwk['n']);
        $exponent = $this->decodeBase64Url($jwk['e']);

        if (strlen($modulus) < 256 || strlen($modulus) > 1024 || $exponent === '') {
            throw new InvalidArgumentException('Invalid Google signing key.');
        }

        $rsaKey = $this->der(0x30, $this->derInteger($modulus).$this->derInteger($exponent));
        $algorithm = hex2bin('300d06092a864886f70d0101010500');
        $subjectPublicKeyInfo = $this->der(0x30, $algorithm.$this->der(0x03, "\0".$rsaKey));
        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($subjectPublicKeyInfo), 64)."-----END PUBLIC KEY-----\n";
        $publicKey = openssl_pkey_get_public($pem);

        if ($publicKey === false) {
            throw new InvalidArgumentException('Invalid Google signing key.');
        }

        $details = openssl_pkey_get_details($publicKey);
        if (! is_array($details) || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA || ($details['bits'] ?? 0) < 2048) {
            throw new InvalidArgumentException('Invalid Google signing key.');
        }

        return $publicKey;
    }

    private function derInteger(string $value): string
    {
        if ((ord($value[0]) & 0x80) !== 0) {
            $value = "\0".$value;
        }

        return $this->der(0x02, $value);
    }

    private function der(int $tag, string $value): string
    {
        $length = strlen($value);

        if ($length < 128) {
            $encodedLength = chr($length);
        } else {
            $bytes = '';
            for ($remaining = $length; $remaining > 0; $remaining >>= 8) {
                $bytes = chr($remaining & 0xff).$bytes;
            }
            $encodedLength = chr(0x80 | strlen($bytes)).$bytes;
        }

        return chr($tag).$encodedLength.$value;
    }
}
