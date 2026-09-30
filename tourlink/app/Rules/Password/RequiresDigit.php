<?php

namespace App\Rules\Password;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class RequiresDigit implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (preg_match('/[0-9]/', $value) !== 1) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return 'Password must contain at least one number.';
    }
}
