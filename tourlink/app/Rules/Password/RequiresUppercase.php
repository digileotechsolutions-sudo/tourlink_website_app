<?php

namespace App\Rules\Password;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The password must hold at least one uppercase ASCII letter.
 *
 * The message is registered centrally in {@see \App\Rules\PasswordRules::messages()}
 * because Laravel resolves messages for rule objects by their short rule name.
 */
final class RequiresUppercase implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (preg_match('/[A-Z]/', $value) !== 1) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return 'Password must contain at least one uppercase letter.';
    }
}
