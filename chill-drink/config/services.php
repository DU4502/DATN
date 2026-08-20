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

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL').'/auth/facebook/callback'),
        'stateless' => env('FACEBOOK_STATELESS', true),
    ],

    'firebase' => [
        'phone_auth' => [
            'project_id' => env('FIREBASE_PROJECT_ID', 'chill-drink-d51d3'),
            'web_config' => [
                'apiKey' => env('FIREBASE_API_KEY', 'AIzaSyAFOsExSbQtGJuBCVlCcFwetBRg7FYNqro'),
                'authDomain' => env('FIREBASE_AUTH_DOMAIN', 'chill-drink-d51d3.firebaseapp.com'),
                'projectId' => env('FIREBASE_PROJECT_ID', 'chill-drink-d51d3'),
                'storageBucket' => env('FIREBASE_STORAGE_BUCKET', 'chill-drink-d51d3.firebasestorage.app'),
                'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID', '594007225015'),
                'appId' => env('FIREBASE_APP_ID', '1:594007225015:web:2133cc417cd0d8ac285332'),
                'measurementId' => env('FIREBASE_MEASUREMENT_ID', 'G-96EY02QVS4'),
            ],
        ],
    ],

    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'return_url' => env('VNPAY_RETURN_URL'),
        'ipn_url' => env('VNPAY_IPN_URL'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'delivery_routing' => [
        // Chỉ là engine tính tuyến bằng tọa độ cho map hiện tại; có thể đổi sang server routing riêng.
        'base_url' => env('ROUTING_BASE_URL', 'https://router.project-osrm.org'),
        'profile' => env('ROUTING_PROFILE', 'driving'),
        'timeout' => env('ROUTING_TIMEOUT', 6),
        // Chỉ dùng cho màn hình dẫn đường shipper: ưu tiên đường nhỏ hơn, rồi mới fallback tuyến thường.
        'navigation_exclude' => env('ROUTING_NAVIGATION_EXCLUDE', 'motorway'),
    ],

    'navigation_tts' => [
        // Một voice Piper cố định chạy local trên server. Không cần API key/cloud.
        'driver' => env('NAV_TTS_DRIVER', 'piper'),
        'piper' => [
            'binary' => env('PIPER_BINARY', 'tools/piper/piper.exe'),
            'model' => env('PIPER_MODEL', 'storage/app/navigation_tts/voices/vi_VN-vais1000-medium.onnx'),
            'config' => env('PIPER_MODEL_CONFIG', 'storage/app/navigation_tts/voices/vi_VN-vais1000-medium.onnx.json'),
            'voice' => env('PIPER_VOICE', 'vi_VN-vais1000-medium'),
            'timeout' => env('PIPER_TIMEOUT', 12),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/').'/auth/google/callback'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/') . '/auth/google/callback'),
        'stateless' => env('GOOGLE_STATELESS', false),
    ],

];
