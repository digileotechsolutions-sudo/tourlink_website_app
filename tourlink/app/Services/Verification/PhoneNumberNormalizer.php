<?php

namespace App\Services\Verification;

use InvalidArgumentException;

class PhoneNumberNormalizer
{
    public function normalize(string $phoneNumber): string
    {
        $digits = preg_replace('/\D/', '', $phoneNumber) ?? '';
        $normalized = str_starts_with($digits, '0') ? '254'.substr($digits, 1) : $digits;

        if (! preg_match('/^254[17]\d{8}$/', $normalized)) {
            throw new InvalidArgumentException('Use a valid Kenyan phone number, for example 0712345678.');
        }

        return $normalized;
    }
}
