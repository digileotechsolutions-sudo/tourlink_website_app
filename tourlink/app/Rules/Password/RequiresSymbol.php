<?php

namespace App\Rules\Password;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A "special character" is anything that is not a letter, a number or
 * whitespace. This accepts the full range of punctuation and symbols instead of
 * a narrow allow list that would reject valid passphrases.
 */
final class RequiresSymbol implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        // Deliberately byte based rather than /u so an unexpected encoding can
        // never turn into a misleading "missing special character" message.
        if (preg_match('/[^A-Za-z0-9\s]/', $value) !== 1) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return 'Password must contain at least one special character.';
    }
}
