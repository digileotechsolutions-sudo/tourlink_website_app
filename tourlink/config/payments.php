<?php

return [
    'pesapal' => [
        'environment' => env('PESAPAL_ENVIRONMENT', 'sandbox'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        'ipn_id' => env('PESAPAL_IPN_ID'),
    ],
];
