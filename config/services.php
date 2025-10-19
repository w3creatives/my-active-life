<?php

declare(strict_types=1);

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

    'strava' => [
        'api_url' => env('STRAVA_API_BASE_URL'),
        'client_id' => env('STRAVA_CLIENT_ID'),
        'redirect_url' => env('STRAVA_REDIRECT_URI'),
        'client_secret' => env('STRAVA_CLIENT_SECRET'),
        'webhook_verification_code' => env('STRAVA_WEBHOOK_VERIFICATION_CODE'),
    ],

    'fitbit' => [
        'client_id' => env('FITBIT_CLIENT_ID'),
        'redirect_url' => env('FITBIT_REDIRECT_URI'),
        'client_secret' => env('FITBIT_CLIENT_SECRET'),
        'webhook_verification_code' => env('FITBIT_WEBHOOK_VERIFICATION_CODE'),
    ],

    'garmin' => [
        'consumer_key' => env('GARMIN_CONSUMER_KEY'),
        'consumer_secret' => env('GARMIN_CONSUMER_SECRET'),
        'callback_url' => env('GARMIN_CALLBACK_URL'),
    ],
    'ouraring' => [
        'client_id' => env('OURARING_CLIENT_ID'),
        'client_secret' => env('OURARING_CLIENT_SECRET'),
        'redirect_url' => env('OURARING_REDIRECT_URI'),
        'webhook_verification_code' => env('OURARING_WEBHOOK_VERIFICATION_CODE'),
    ],
    'tracker' => [
        'workflow_url' => env('TRACKER_USER_POINT_WORKFLOW'),
    ],
];
