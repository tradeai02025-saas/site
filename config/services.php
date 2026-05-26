<?php

return [

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

    'virustotal' => [
        'api_key' => env('VIRUSTOTAL_API_KEY'),
        'base_url' => 'https://www.virustotal.com/api/v3/',
        'enabled' => env('VIRUSTOTAL_ENABLED', false),
        'timeout' => 30,
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'pi_chat_id' => env('TELEGRAM_PI_CHAT_ID'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'admin_user_ids' => array_filter(array_map('trim', explode(',', env('TELEGRAM_ADMIN_USER_IDS', '')))),
        'enabled' => env('TELEGRAM_ENABLED', false),
        'throttle_minutes' => (int) env('TELEGRAM_THROTTLE_MINUTES', 5),
    ],

];