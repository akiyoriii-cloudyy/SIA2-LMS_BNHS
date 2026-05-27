<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],

    'phpmailer' => [
        'host' => env('PHPM_MAIL_HOST', 'smtp.gmail.com'),
        'port' => (int) env('PHPM_MAIL_PORT', 587),
        'username' => env('PHPM_MAIL_USERNAME'),
        'password' => env('PHPM_MAIL_PASSWORD'),
        'encryption' => env('PHPM_MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('PHPM_MAIL_FROM_ADDRESS', env('PHPM_MAIL_USERNAME')),
        'from_name' => env('PHPM_MAIL_FROM_NAME', env('APP_NAME', 'BNHS LMS')),
    ],

];
