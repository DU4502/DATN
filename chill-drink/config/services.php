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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'password_reset' => [
        'expire_minutes' => env('PASSWORD_RESET_EXPIRE_MINUTES', 60),
        'smtp_host' => env('PASSWORD_RESET_SMTP_HOST'),
        'smtp_port' => env('PASSWORD_RESET_SMTP_PORT', 587),
        'smtp_encryption' => env('PASSWORD_RESET_SMTP_ENCRYPTION', 'tls'),
        'smtp_username' => env('PASSWORD_RESET_SMTP_USERNAME'),
        'smtp_password' => env('PASSWORD_RESET_SMTP_PASSWORD'),
        'from_address' => env('PASSWORD_RESET_FROM_ADDRESS'),
        'from_name' => env('PASSWORD_RESET_FROM_NAME', 'Chill Drink'),
    ],

];
