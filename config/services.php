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

    'bkash_sms' => [
        'token' => env('BKASH_SMS_WEBHOOK_TOKEN'),
    ],

    // WhatsApp Cloud API (Meta) - used to send a payment-received reply
    // after a bKash SMS is processed. See App\Services\WhatsAppService.
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        // Approved template used for the payment confirmation. Its body is
        // expected to take 4 positional variables, in this order:
        //   {{1}} party name   {{2}} amount   {{3}} TrxID   {{4}} date
        'payment_template' => env('WHATSAPP_PAYMENT_TEMPLATE', 'payment_received'),
        'payment_template_language' => env('WHATSAPP_PAYMENT_TEMPLATE_LANG', 'en'),
    ],

];
