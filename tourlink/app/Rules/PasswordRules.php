<?php

namespace App\Rules;

use App\Rules\Password\NotBreachedPassword;
use App\Rules\Password\NotCommonPassword;
use App\Rules\Password\NotDerivedFromAccount;
use App\Rules\Password\RequiresDigit;
use App\Rules\Password\RequiresLowercase;
use App\Rules\Password\RequiresSymbol;
use App\Rules\Password\RequiresUppercase;

/**
 * Single source of truth for TourLink's password policy.
 *
 * Every place that accepts a new password (registration, reset, change and the
 * admin creation command) uses {@see static::rules()} together with
 * {@see static::messages()} so the policy and its wording cannot drift apart.
 *
 * The policy is deliberately not applied to sign-in: existing accounts may still
 * hold a password created under an older policy and must remain able to log in.
 */
final class PasswordRules
{
    public const MIN_LENGTH = 8;

    /**
     * bcrypt silently truncates at 72 bytes, so anything longer is rejected
     * rather than quietly weakened.
     */
    public const MAX_LENGTH = 72;

    /**
     * @return list<mixed>
     */
    public static function rules(int $minLength = self::MIN_LENGTH): array
    {
        return [
            'required',
            'string',
            'min:'.$minLength,
            'max:'.self::MAX_LENGTH,
            new RequiresUppercase,
            new RequiresLowercase,
            new RequiresDigit,
            new RequiresSymbol,
            new NotCommonPassword,
            new NotBreachedPassword,
        ];
    }

    /**
     * Messages for rule objects are resolved by their short rule name
     * (App\Rules\Password\RequiresDigit becomes "requires_digit"), which is why
     * every custom rule is registered here.
     *
     * @return array<string, array<string, string>>
     */
    public static function messages(int $minLength = self::MIN_LENGTH): array
    {
        return [
            'password' => [
                'min' => sprintf('Password must be at least %d characters.', $minLength),
                'max' => sprintf('Password must not be longer than %d characters.', self::MAX_LENGTH),
                'confirmed' => 'Password confirmation does not match.',
                'current_password' => 'Your current password is incorrect.',
                'requires_uppercase' => (new RequiresUppercase)->message(),
                'requires_lowercase' => (new RequiresLowercase)->message(),
                'requires_digit' => (new RequiresDigit)->message(),
                'requires_symbol' => (new RequiresSymbol)->message(),
                'not_common_password' => (new NotCommonPassword)->message(),
                'not_breached_password' => (new NotBreachedPassword)->message(),
                'not_derived_from_account' => (new NotDerivedFromAccount)->message(),
            ],
            'current_password' => [
                'current_password' => 'Your current password is incorrect.',
            ],
        ];
    }

    /**
     * Appends the account-identifier check, which needs the request context or
     * the authenticated user.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function withAccountContext(array $rules, mixed ...$context): array
    {
        $rules[] = new NotDerivedFromAccount(...$context);

        return $rules;
    }

    /**
     * Client side rule hints shared with the password-strength component so the
     * browser never claims a requirement the server does not enforce.
     *
     * @return array<string, mixed>
     */
    public static function frontendHints(): array
    {
        return [
            'minLength' => self::MIN_LENGTH,
            'maxLength' => self::MAX_LENGTH,
        ];
    }
}
