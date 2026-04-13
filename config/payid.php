<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    | Driver yang digunakan secara default jika tidak ditentukan secara eksplisit.
    | Nilai harus sesuai dengan salah satu key di array 'drivers' di bawah.
    | Driver aktif saat ini: midtrans | xendit | ipaymu | nicepay
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
    |
    | Aktifkan driver dengan:
    |   1. composer require aliziodev/payid-<driver>
    |   2. Set env variable yang sesuai di .env
    |   3. Set PAYID_DEFAULT_DRIVER ke nama driver
    |
    | Catatan:
    |   - Driver aktif: midtrans, xendit, ipaymu, nicepay
    |   - Coming soon: doku, oyid, tripay
    */
    'drivers' => [

        /*
        |----------------------------------------------------------------------
        | Midtrans
        |----------------------------------------------------------------------
        | Dokumentasi: https://docs.midtrans.com
        | Sandbox Dashboard: https://dashboard.sandbox.midtrans.com
        | Production Dashboard: https://dashboard.midtrans.com
        */
        'midtrans' => [
            'driver' => 'midtrans',
            'environment' => env('MIDTRANS_ENV', 'sandbox'), // sandbox | production
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
            'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
            'order_prefix' => env('MIDTRANS_ORDER_PREFIX', ''),
            // Override URL endpoint. Kosongkan untuk auto-detect dari 'environment'.
            'endpoints' => [
                'snap_base_url' => env('MIDTRANS_SNAP_URL'),
                'core_base_url' => env('MIDTRANS_CORE_URL'),
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Xendit
        |----------------------------------------------------------------------
        | Dokumentasi: https://developers.xendit.co
        | Dashboard: https://dashboard.xendit.co
        */
        'xendit' => [
            'driver' => 'xendit',
            'environment' => env('XENDIT_ENV', 'test'), // test | live
            'secret_key' => env('XENDIT_SECRET_KEY'),
            'public_key' => env('XENDIT_PUBLIC_KEY'),
            'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        ],

        /*
        |----------------------------------------------------------------------
        | iPaymu
        |----------------------------------------------------------------------
        | Dokumentasi: https://ipaymu.com/api
        | Sandbox Dashboard: https://sandbox.ipaymu.com
        | Production Dashboard: https://my.ipaymu.com
        */
        'ipaymu' => [
            'driver' => 'ipaymu',
            'environment' => env('IPAYMU_ENV', 'sandbox'), // sandbox | production
            'va' => env('IPAYMU_VA'),
            'api_key' => env('IPAYMU_API_KEY'),
            'base_url' => env('IPAYMU_BASE_URL'),
            'timeout' => (int) env('IPAYMU_TIMEOUT', 30),
            'webhook_verification_enabled' => (bool) env('IPAYMU_WEBHOOK_VERIFY', false),
            'webhook_token' => env('IPAYMU_WEBHOOK_TOKEN'),
            'webhook_signature_key' => env('IPAYMU_WEBHOOK_SIGNATURE_KEY'),
            'payment_path' => env('IPAYMU_PAYMENT_PATH', '/api/v2/payment'),
            'direct_payment_path' => env('IPAYMU_DIRECT_PAYMENT_PATH', '/api/v2/payment/direct'),
            'payment_channel_path' => env('IPAYMU_PAYMENT_CHANNEL_PATH', '/api/v2/payment-channel'),
            'transaction_path' => env('IPAYMU_TRANSACTION_PATH', '/api/v2/transaction'),
            'balance_path' => env('IPAYMU_BALANCE_PATH', '/api/v2/balance'),
            'history_path' => env('IPAYMU_HISTORY_PATH', '/api/v2/history'),
        ],

        /*
        |----------------------------------------------------------------------
        | Nicepay
        |----------------------------------------------------------------------
        | Dokumentasi: https://docs.nicepay.co.id
        | SDK Official: https://github.com/nicepay-dev/php-nicepay
        */
        'nicepay' => [
            'driver' => 'nicepay',
            'environment' => env('NICEPAY_ENV', 'sandbox'), // sandbox | production
            'merchant_id' => env('NICEPAY_MERCHANT_ID'),
            'client_secret' => env('NICEPAY_CLIENT_SECRET'),
            'private_key' => env('NICEPAY_PRIVATE_KEY'),
            'merchant_key' => env('NICEPAY_MERCHANT_KEY'),
            'partner_id' => env('NICEPAY_PARTNER_ID'),
            'base_url' => env('NICEPAY_BASE_URL'),
            'timeout' => (int) env('NICEPAY_TIMEOUT', 30),
            'webhook_verification_enabled' => (bool) env('NICEPAY_WEBHOOK_VERIFY', false),
            'webhook_token' => env('NICEPAY_WEBHOOK_TOKEN'),
            'webhook_public_key' => env('NICEPAY_WEBHOOK_PUBLIC_KEY'),
            'payment_path' => env('NICEPAY_PAYMENT_PATH', '/api/v1.0/debit/payment-host-to-host'),
            'status_path' => env('NICEPAY_STATUS_PATH', '/api/v1.0/debit/status'),
        ],

        /*
        |----------------------------------------------------------------------
        | Coming Soon Drivers
        |----------------------------------------------------------------------
        | DOKU, OY! Indonesia, dan Tripay masih roadmap.
        | Konfigurasi aktif sengaja belum disediakan agar tidak ambigu.
        */

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
