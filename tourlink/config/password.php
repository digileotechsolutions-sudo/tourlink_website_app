<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Breach Check
    |--------------------------------------------------------------------------
    |
    | Passwords are checked against the Have I Been Pwned range API using
    | k-anonymity, so only the first five characters of the SHA-1 digest are
    | ever sent. The lookup is cached per prefix, times out fast and fails open,
    | meaning an unreachable API never blocks a legitimate sign-up. Set
    | PASSWORD_BREACH_CHECK=false to disable it entirely, for example on a host
    | without outbound HTTPS.
    |
    */

    'breach_check' => [
        'enabled' => env('PASSWORD_BREACH_CHECK', true),
        'url' => env('PASSWORD_BREACH_URL', 'https://api.pwnedpasswords.com/range'),
        'timeout' => (float) env('PASSWORD_BREACH_TIMEOUT', 1),
        'cache_seconds' => (int) env('PASSWORD_BREACH_CACHE_SECONDS', 3600),
    ],

];
