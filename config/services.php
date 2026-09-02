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
   

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    'infobip' => [
        'api_url' => env('INFOBIP_API_URL'),
        'api_key' => env('INFOBIP_API_KEY'),
        'sender' => env('INFOBIP_SENDER', 'InfoSMS')
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'req_supply' => [
        'url' => env('REQ_SUPPLY_URL'),
        'token' => env('REQ_SUPPLY_TOKEN'),
        'timeout' => env('REQ_SUPPLY_TIMEOUT', 10),
    ],

    'hr_employees' => [
        'url' => env('HR_EMPLOYEES_URL'),
        'token' => env('HR_EMPLOYEES_TOKEN'),
        'timeout' => env('HR_EMPLOYEES_TIMEOUT', 20),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
