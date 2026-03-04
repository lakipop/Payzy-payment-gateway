<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payzy Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Payzy payment gateway.
    | Get your Shop ID and Secret Key from the Payzy merchant dashboard.
    |
    */

    // Set to true for testing, false for production
    'test_mode' => env('PAYZY_TEST_MODE', true),

    // Your Payzy Shop ID (from Payzy Dashboard)
    'shop_id' => env('PAYZY_SHOP_ID', ''),

    // Your Payzy Secret Key (from Payzy Dashboard)
    'secret_key' => env('PAYZY_SECRET_KEY', ''),

    // Payzy API endpoint
    'api_url' => env('PAYZY_API_URL', 'https://api.payzy.lk/checkout/custom-checkout'),

    // Callback URL - Payzy will redirect the customer here after payment
    // IMPORTANT: This must be a publicly accessible URL
    'response_url' => env('PAYZY_RESPONSE_URL', ''),

    // Platform identifier (keep as 'custom' for standard integration)
    'platform' => 'custom',

    // API version
    'version' => '1.0',
];
