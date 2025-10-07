<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payzy Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the Payzy Payment Gateway.
    | You can customize these settings based on your application requirements.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Set to true for testing, false for production.
    | In test mode, no real money transactions will occur.
    |
    */
    'test_mode' => env('PAYZY_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Shop Credentials
    |--------------------------------------------------------------------------
    |
    | Your Payzy shop ID and secret key provided by Payzy.
    | These are required for authentication with the Payzy API.
    |
    */
    'shop_id' => env('PAYZY_SHOP_ID', ''),
    'secret_key' => env('PAYZY_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Payzy API endpoints and platform settings.
    |
    */
    'api_url' => env('PAYZY_API_URL', 'https://api.payzy.lk/checkout/custom-checkout'),
    'response_url' => env('PAYZY_RESPONSE_URL', ''),
    'platform' => env('PAYZY_PLATFORM', 'custom'),
    'version' => env('PAYZY_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for payments if not specified.
    |
    */
    'default_currency' => env('PAYZY_DEFAULT_CURRENCY', 'LKR'),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the database table names and model relationships.
    |
    */
    'table_names' => [
        'payments' => 'payzy_payments',
        'payment_items' => 'payzy_payment_items',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which models to use for relationships.
    | You can customize these to use your own models.
    |
    */
    'user_model' => env('PAYZY_USER_MODEL', 'App\Models\User'),
    'product_model' => env('PAYZY_PRODUCT_MODEL', null), // Optional: Set if you have a Product model

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for Payzy payment processing.
    |
    */
    'logging' => [
        'enabled' => env('PAYZY_LOGGING_ENABLED', true),
        'channel' => env('PAYZY_LOG_CHANNEL', 'daily'),
        'level' => env('PAYZY_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook settings for payment notifications.
    |
    */
    'webhook' => [
        'enabled' => env('PAYZY_WEBHOOK_ENABLED', false),
        'url' => env('PAYZY_WEBHOOK_URL', ''),
        'secret' => env('PAYZY_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which events to fire during payment processing.
    |
    */
    'events' => [
        'payment_initiated' => env('PAYZY_EVENTS_PAYMENT_INITIATED', true),
        'payment_verified' => env('PAYZY_EVENTS_PAYMENT_VERIFIED', true),
        'payment_failed' => env('PAYZY_EVENTS_PAYMENT_FAILED', true),
        'payment_cancelled' => env('PAYZY_EVENTS_PAYMENT_CANCELLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for payment data.
    |
    */
    'validation' => [
        'min_amount' => env('PAYZY_MIN_AMOUNT', 0.01),
        'max_amount' => env('PAYZY_MAX_AMOUNT', 999999.99),
        'allowed_currencies' => ['LKR', 'USD', 'EUR', 'GBP'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout Configuration
    |--------------------------------------------------------------------------
    |
    | Configure timeout settings for API requests.
    |
    */
    'timeout' => [
        'api_request' => env('PAYZY_API_TIMEOUT', 30), // seconds
        'payment_expiry' => env('PAYZY_PAYMENT_EXPIRY', 3600), // seconds (1 hour)
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure route settings for the package endpoints.
    |
    */
    'routes' => [
        'prefix' => env('PAYZY_ROUTE_PREFIX', 'payzy'),
        'middleware' => explode(',', env('PAYZY_ROUTE_MIDDLEWARE', 'web')),
        'namespace' => 'PayzyLaravel\PaymentGateway\Controllers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for payment data and API responses.
    |
    */
    'cache' => [
        'enabled' => env('PAYZY_CACHE_ENABLED', false),
        'ttl' => env('PAYZY_CACHE_TTL', 300), // seconds (5 minutes)
        'prefix' => env('PAYZY_CACHE_PREFIX', 'payzy'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the payment gateway.
    |
    */
    'features' => [
        'auto_verify_payments' => env('PAYZY_AUTO_VERIFY_PAYMENTS', true),
        'store_payment_items' => env('PAYZY_STORE_PAYMENT_ITEMS', true),
        'enable_refunds' => env('PAYZY_ENABLE_REFUNDS', false),
        'enable_recurring_payments' => env('PAYZY_ENABLE_RECURRING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for the payment gateway.
    |
    */
    'security' => [
        'verify_ssl' => env('PAYZY_VERIFY_SSL', true),
        'allowed_ips' => explode(',', env('PAYZY_ALLOWED_IPS', '')),
        'signature_verification' => env('PAYZY_VERIFY_SIGNATURE', true),
    ],
];