<?php

namespace App\Rules\Password;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Practical breach detection via the Have I Been Pwned range API.
 *
 * Only the first five characters of the SHA-1 digest leave the application, so
 * neither the password nor its full hash is ever transmitted or stored. Results
 * are cached per prefix and the check fails open: an unreachable or slow API
 * never blocks a legitimate sign-up.
 */
final class NotBreachedPassword implements ValidationRule
{
    private const PREFIX_LENGTH = 5;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || ! config('password.breach_check.enabled', true)) {
            return;
        }

        if ($this->wasBreached($value)) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return 'This password has appeared in a data breach. Please choose a different one.';
    }

    private function wasBreached(string $value): bool
    {
        $digest = strtoupper(sha1($value));
        $prefix = substr($digest, 0, self::PREFIX_LENGTH);
        $suffix = substr($digest, self::PREFIX_LENGTH);

        try {
            $breachedSuffixes = Cache::remember(
                'password.breach.suffixes.'.$prefix,
                now()->addSeconds((int) config('password.breach_check.cache_seconds', 3600)),
                fn (): array => $this->lookup($prefix),
            );

            return is_array($breachedSuffixes) && in_array($suffix, $breachedSuffixes, true);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function lookup(string $prefix): array
    {
        try {
            $response = Http::withHeaders(['Add-Padding' => 'true'])
                ->timeout((float) config('password.breach_check.timeout', 1))
                ->retry(0)
                ->get(rtrim((string) config('password.breach_check.url'), '/').'/'.$prefix);

            if (! $response->successful()) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        $suffixes = [];
        foreach (preg_split('/\R/', $response->body()) ?: [] as $line) {
            $parts = explode(':', trim($line), 2);

            if (count($parts) === 2 && preg_match('/^[A-F0-9]{35}$/i', $parts[0]) === 1) {
                $suffixes[] = strtoupper($parts[0]);
            }
        }

        return $suffixes;
    }
}
