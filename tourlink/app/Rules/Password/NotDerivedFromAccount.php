<?php

namespace App\Rules\Password;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;

/**
 * Rejects passwords that contain the account holder's own name, email address or
 * phone number. Identifiers shorter than four characters are ignored so short
 * names and short email local parts cannot reject an unrelated password.
 */
final class NotDerivedFromAccount implements ValidationRule
{
    private const MIN_IDENTIFIER_LENGTH = 4;

    /**
     * @var list<string>
     */
    private readonly array $identifiers;

    public function __construct(mixed ...$values)
    {
        $candidates = [];

        foreach ($values as $value) {
            if ($value instanceof User) {
                $candidates[] = $value->name;
                $candidates[] = $value->email;
                $candidates[] = $value->phone;
            } elseif ($value instanceof Request) {
                $candidates[] = $value->input('name');
                $candidates[] = $value->input('email');
                $candidates[] = $value->input('phone');
                $candidates[] = $value->user()?->name;
                $candidates[] = $value->user()?->email;
                $candidates[] = $value->user()?->phone;
            } else {
                $candidates[] = $value;
            }
        }

        $this->identifiers = static::prepare($candidates);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || $this->identifiers === []) {
            return;
        }

        $needle = NotCommonPassword::normalise($value);

        foreach ($this->identifiers as $identifier) {
            if ($identifier !== '' && str_contains($needle, $identifier)) {
                $fail($this->message());

                return;
            }
        }
    }

    public function message(): string
    {
        return 'Password must not contain your name, email address or phone number.';
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<string>
     */
    private static function prepare(array $values): array
    {
        $identifiers = [];

        foreach ($values as $value) {
            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $identifier = NotCommonPassword::normalise($value);

            if (strlen($identifier) >= self::MIN_IDENTIFIER_LENGTH) {
                $identifiers[] = $identifier;
                continue;
            }

            // An email address is also reduced to its local part so that
            // "michael@example.com" is rejected for "michael@example".
            if (str_contains($value, '@')) {
                [$localPart] = explode('@', $value, 2);

                $local = NotCommonPassword::normalise($localPart);

                if (strlen($local) >= self::MIN_IDENTIFIER_LENGTH) {
                    $identifiers[] = $local;
                }
            }
        }

        return array_values(array_unique($identifiers));
    }
}
