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

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    'giata' => [
        'base_url' => env('GIATA_BASE_URL'),
        'user'     => env('GIATA_USER'),
        'pass'     => env('GIATA_PASSWORD'),
    ],
    'samo' => [
        'base_url' => env('SAMO_BASE_URL', 'http://lib-samo.dome-consulting.com/service'),
        'username' => env('SAMO_USERNAME', 'JU_SAMO_XML'),
        'password' => env('SAMO_PASSWORD', 'JU_SAMO_XML77'),
        'claim_number' => env('SAMO_CLAIM_NUMBER', 1510263),
    ],

];
