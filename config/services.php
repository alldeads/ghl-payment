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

    'ghl' => [
        'app_id' => env('GHL_APP_ID'),
        'client_id' => env('GHL_CLIENT_ID'),
        'client_secret' => env('GHL_CLIENT_SECRET'),
        'redirect_uri' => env('GHL_REDIRECT_URI'),
        'scopes' => env('GHL_SCOPES', 'contacts.readonly contacts.write locations.readonly opportunities.readonly opportunities.write calendars.readonly calendars.write users.readonly workflows.readonly'),
        'oauth_base_url' => env('GHL_OAUTH_BASE_URL', 'https://marketplace.gohighlevel.com'),
        'api_base_url' => env('GHL_API_BASE_URL', 'https://services.leadconnectorhq.com'),
        'webhook_secret' => env('GHL_WEBHOOK_SECRET'),
    ],

    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'public_key' => env('XENDIT_PUBLIC_KEY'),
        'callback_token' => env('XENDIT_CALLBACK_TOKEN', env('XENDIT_WEBHOOK_VERIFICATION_TOKEN')),
        'callback_url' => env('XENDIT_CALLBACK_URL', env('APP_URL').'/xendit/webhook'),
        'api_base_url' => env('XENDIT_API_BASE_URL', 'https://api.xendit.co'),
        'invoice_duration_seconds' => (int) env('XENDIT_INVOICE_DURATION_SECONDS', 86400),
        'success_redirect_url' => env('XENDIT_SUCCESS_REDIRECT_URL', env('APP_URL').'/ghl/dashboard'),
        'failure_redirect_url' => env('XENDIT_FAILURE_REDIRECT_URL', env('APP_URL').'/ghl/dashboard'),
    ],

];
