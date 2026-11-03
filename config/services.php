<?php

return [

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
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'wazuh' => [
        'url' => env('WAZUH_INDEXER_URL'),
        'username' => env('WAZUH_INDEXER_USERNAME'),
        'password' => env('WAZUH_INDEXER_PASSWORD'),
        // true, false, ou chemin vers le certificat racine (root-ca.pem) de l'Indexer
        'verify' => env('WAZUH_INDEXER_VERIFY', true),
        'index' => env('WAZUH_ALERTS_INDEX', 'wazuh-alerts-4.x-*'),
        'min_level' => (int) env('WAZUH_MIN_LEVEL', 8),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
