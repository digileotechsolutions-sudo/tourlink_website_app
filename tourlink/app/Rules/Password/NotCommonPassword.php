<?php

namespace App\Rules\Password;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks the passwords that show up at the top of every credential dump.
 *
 * Comparison is done on a normalised form (lower case, alphanumeric only) so
 * "P@ssw0rd", "p-assw0rd" and "PASSW0RD" all resolve to the same entry, and a
 * short trailing run of digits such as "password2024" is matched too.
 */
final class NotCommonPassword implements ValidationRule
{
    /**
     * The minimum length a candidate must have before the trailing digits of a
     * known password are checked. Below this, "abc123" style padding would trip
     * the rule on passwords that are already rejected for being too simple.
     */
    private const MIN_LENGTH_BEFORE_SUFFIX_CHECK = 10;

    /**
     * @var list<string>|null
     */
    private static ?array $normalisedCommonPasswords = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $normalised = static::normalise($value);

        $commonPasswords = static::normalisedCommonPasswords();

        if (in_array($normalised, $commonPasswords, true)) {
            $fail($this->message());

            return;
        }

        if (strlen($normalised) >= self::MIN_LENGTH_BEFORE_SUFFIX_CHECK) {
            $trimmed = preg_replace('/\d{1,4}$/', '', $normalised) ?? $normalised;

            if ($trimmed !== $normalised && in_array($trimmed, $commonPasswords, true)) {
                $fail($this->message());
            }
        }
    }

    public function message(): string
    {
        return 'Password must not be a commonly used password.';
    }

    public static function normalise(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower($value));
    }

    /**
     * @return list<string>
     */
    private static function normalisedCommonPasswords(): array
    {
        return self::$normalisedCommonPasswords ??= array_values(array_unique(array_map(
            static fn (string $password): string => self::normalise($password),
            self::commonPasswords(),
        )));
    }

    /**
     * @return list<string>
     */
    public static function commonPasswords(): array
    {
        return [
            '123456', '1234567', '12345678', '123456789', '1234567890', '12345', '12345678910',
            'password', 'passwd', 'password1', 'password123', 'password1234', 'password01', 'p@ssw0rd', 'p@ssword',
            'admin', 'admin123', 'admin1234', 'administrator', 'root', 'root123', 'toor',
            'qwerty', 'qwerty123', 'qwertyuiop', 'qwerty1', 'asdfghjkl', 'asdf1234', 'zxcvbnm', 'zxcvbn',
            'letmein', 'letmein123', 'welcome', 'welcome1', 'welcome123', 'welcome2024',
            'monkey', 'dragon', 'sunshine', 'princess', 'football', 'baseball', 'soccer', 'hockey',
            'superman', 'batman', 'starwars', 'trustno1', 'iloveyou', 'shadow', 'ashley', 'michael',
            'jennifer', 'jessica', 'charlie', 'daniel', 'michelle', 'thomas', 'robert', 'william',
            'master', 'killer', 'soccer1', 'chelsea', 'liverpool', 'arsenal', 'barcelona', 'juventus',
            'ninja', 'access', 'flower', 'mustang', 'diamond', 'secret', 'love', 'loveme', 'nope',
            'abc123', 'abc1234', 'abcd1234', 'a1b2c3', 'test', 'testing', 'testing123', 'test123',
            'guest', 'guest123', 'login', 'user', 'user123', 'default', 'changeme', 'change123',
            'temp', 'temp123', 'temp1234', 'pass', 'pass123', 'pass1234', 'p@ss', 'p@ssword1', 'p@ss1234',
            'star', 'starwars1', 'freedom', 'whatever', 'nothing', 'hello', 'hello123', 'hello1',
            'michael1', 'jordan', 'jordan23', 'thunder', 'soccer12', 'anthony', 'amanda', 'summer',
            'chelsea1', 'beautiful', 'purple', 'ginger', 'orange', 'yellow', 'silver', 'diamond1',
            'tigger', 'joshua', 'maggie', 'pepper', 'cheese', 'cookie', 'pizza', 'coffee', 'beer',
            'summer2024', 'winter2024', 'spring2024', 'autumn2024', 'january', 'february', 'march',
            'october', 'november', 'december', 'company', 'company123', 'business', 'office',
            'computer', 'internet', 'laptop', 'myspace1', 'facebook', 'twitter', 'linkedin',
            'iloveu', 'lovely', 'darling', 'honey', 'babygirl', 'poohbear', 'tinkerbell',
            'whatever1', 'nothing1', 'baseball1', 'football1', 'minecraft', 'fortnite', 'roblox',
            'killer1', 'master1', 'monkey1', 'dragon1', 'shadow1', 'superman1', 'batman1',
            '696969', '123abc', '1q2w3e4r', 'q1w2e3r4', 'qwerty12345', 'abcd123456',
            'zaq12wsx', 'qazwsx', 'passw0rd', 'p4ssw0rd', 'pa55word', 'pa55w0rd', 'p455w0rd',
            'tourlink', 'tourlink1', 'tourlink123', 'kenya', 'nairobi', 'safari', 'matatu',
            'mustang1', 'corvette', 'camaro', 'ferrari', 'porsche', 'mercedes', 'benz',
        ];
    }
}
