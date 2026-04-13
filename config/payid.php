<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    | Driver yang digunakan secara default jika tidak ditentukan secara eksplisit.
    | Nilai harus sesuai dengan salah satu key di array 'drivers' di bawah.
    */
    'default' => env('PAYID_DEFAULT_DRIVER', 'midtrans'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('PAYID_DEFAULT_CURRENCY', 'IDR'),

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers
    |--------------------------------------------------------------------------
    | Daftar driver yang dikonfigurasi. Key adalah nama driver (identifier),
    | value adalah array konfigurasi yang akan diteruskan ke driver.
    | Key 'driver' di dalam config merujuk ke nama driver yang terdaftar.
    */
    'drivers' => [

        'midtrans' => [
            'driver' => 'midtrans',
            'environment' => env('MIDTRANS_ENV', 'sandbox'),
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
            'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
            // Override endpoint URLs. Leave null to auto-detect from 'environment'.
            'endpoints' => [
                'snap_base_url' => env('MIDTRANS_SNAP_URL'),
                'core_base_url' => env('MIDTRANS_CORE_URL'),
            ],
        ],

        'xendit' => [
            'driver' => 'xendit',
            'environment' => env('XENDIT_ENV', 'test'),
            'secret_key' => env('XENDIT_SECRET_KEY'),
            'public_key' => env('XENDIT_PUBLIC_KEY'),
            'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Settings
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => (int) env('PAYID_HTTP_TIMEOUT', 30),
        'retry_times' => (int) env('PAYID_HTTP_RETRY', 1),
        'retry_delay_ms' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'route_prefix' => env('PAYID_WEBHOOK_PREFIX', 'payid'),
        'route_middleware' => [],
        'queue' => (bool) env('PAYID_WEBHOOK_QUEUE', false),
        'queue_name' => env('PAYID_WEBHOOK_QUEUE_NAME', 'default'),
        'queue_connection' => env('PAYID_WEBHOOK_QUEUE_CONNECTION', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => (bool) env('PAYID_LOGGING', true),
        'channel' => env('PAYID_LOG_CHANNEL', null),
        'mask_sensitive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Credential Resolver (untuk multi-tenant)
    |--------------------------------------------------------------------------
    | Isi dengan callable jika credential perlu di-resolve secara dinamis.
    | Signature: function(string $driver): array
    |
    | Contoh penggunaan di AppServiceProvider:
    | PayId::resolveCredentialsUsing(fn ($driver) => Tenant::current()->credentials($driver));
    */
    'credential_resolver' => null,

];
