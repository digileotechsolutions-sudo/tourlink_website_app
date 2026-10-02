<?php

return [

    'tourlink' => [
        'support_email' => env('TOURLINK_SUPPORT_EMAIL') ?: 'tourlink@havenedgerealtors.com',
        'support_phone' => env('TOURLINK_SUPPORT_PHONE') ?: '+254783366409',
        'support_phone_secondary' => env('TOURLINK_SUPPORT_PHONE_SECONDARY') ?: '+254799591373',
        'location' => env('TOURLINK_LOCATION'),
        'social' => [
            'facebook' => env('TOURLINK_FACEBOOK_URL'),
            'instagram' => env('TOURLINK_INSTAGRAM_URL'),
            'x' => env('TOURLINK_X_URL'),
            'linkedin' => env('TOURLINK_LINKEDIN_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
        'from' => env('EMAIL_FROM'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID') ?: '774417894577-u3rmi4unflbi83bp2kkodn4ugclbjasd.apps.googleusercontent.com',
    ],

    'africastalking' => [
        'username' => env('AT_USERNAME'),
        'api_key' => env('AT_API_KEY'),
        'sender_id' => env('AT_SENDER_ID'),
    ],

    'otp' => [
        'dev_mode' => (bool) env('OTP_DEV_MODE', false),
        'delivery' => env('OTP_DELIVERY', 'auto'),
        'email_transport' => env('OTP_EMAIL_TRANSPORT', 'auto'),
    ],

    'mpesa' => [
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'passkey' => env('MPESA_PASSKEY'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
