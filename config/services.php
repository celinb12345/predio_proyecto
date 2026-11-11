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

    'pagofacil' => [
    'url' => env('PAGOFACIL_URL', 'https://masterqr.pagofacil.com.bo/api/services/v2'),
    'secret' => env('PAGOFACIL_TOKEN_SECRET'),
    'service' => env('PAGOFACIL_TOKEN_SERVICE'),
    'metodo_id' => env('PAGOFACIL_PAYMENT_METHOD_ID'),
    'moneda' => env('PAGOFACIL_MONEDA', 2),
    'callback_url' => env('PAGOFACIL_CALLBACK_URL'),
    'estado_pagado' => env('PAGOFACIL_ESTADO_PAGADO'),
    'timeout' => env('PAGOFACIL_TIMEOUT', 15),
],

];
