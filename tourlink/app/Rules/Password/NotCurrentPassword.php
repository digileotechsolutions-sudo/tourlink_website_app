<?php

namespace App\Rules\Password;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

final class NotCurrentPassword implements ValidationRule
{
    public function __construct(private readonly string $currentPasswordHash) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && $value !== '' && Hash::check($value, $this->currentPasswordHash)) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return 'Your new password must be different from your current password.';
    }
}
