<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PipraPay API Configuration
    |--------------------------------------------------------------------------
    |
    | Base URL and server-side secret API Key for communicating with
    | PipraPay Payment Gateway. Keep the API key secure and never expose
    | it in frontend code or client network calls.
    |
    */
    'base_url' => rtrim(env('PIPRAPAY_BASE_URL', 'https://pay.softsasi.com/api'), '/'),
    'api_key'  => env('PIPRAPAY_API_KEY', '864195898a4e265e6c38cc5b060a97cfd92c983a4bf2f70ddc'),
    'currency' => env('PIPRAPAY_CURRENCY', 'BDT'),

    'endpoints' => [
        'checkout' => '/checkout/redirect',
        'verify'   => '/verify-payment',
        'refund'   => '/refund-payment',
    ],
];
