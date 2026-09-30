<?php

namespace App\Rules\Password;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class RequiresLowercase implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (preg_match('/[a-z]/', $value) !== 1) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return 'Password must contain at least one lowercase letter.';
    }
}
