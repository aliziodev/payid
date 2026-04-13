<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    | Driver yang digunakan secara default jika tidak ditentukan secara eksplisit.
    | Nilai harus sesuai dengan salah satu key di array 'drivers' di bawah.
    | Contoh: midtrans | xendit | doku | ipaymu | nicepay | oyid | tripay
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
        | DOKU
        |----------------------------------------------------------------------
        | Dokumentasi: https://developers.doku.com
        | Sandbox Dashboard: https://sandbox.doku.com
        | Production Dashboard: https://dashboard.doku.com
        */
        'doku' => [
            'driver' => 'doku',
            'environment' => env('DOKU_ENV', 'sandbox'), // sandbox | production
            'client_id' => env('DOKU_CLIENT_ID'),
            'secret_key' => env('DOKU_SECRET_KEY'),
            'shared_key' => env('DOKU_SHARED_KEY'),
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
        ],

        /*
        |----------------------------------------------------------------------
        | Nicepay (PT Ionpay Networks)
        |----------------------------------------------------------------------
        | Dokumentasi: https://docs.nicepay.co.id
        | Dashboard: https://merchant.nicepay.co.id
        */
        'nicepay' => [
            'driver' => 'nicepay',
            'environment' => env('NICEPAY_ENV', 'sandbox'), // sandbox | production
            'merchant_id' => env('NICEPAY_MERCHANT_ID'),   // iMid
            'merchant_key' => env('NICEPAY_MERCHANT_KEY'),
        ],

        /*
        |----------------------------------------------------------------------
        | OY! Indonesia
        |----------------------------------------------------------------------
        | Dokumentasi: https://api-docs.oyindonesia.com
        | Dashboard: https://desktop.oyindonesia.com
        */
        'oyid' => [
            'driver' => 'oyid',
            'environment' => env('OYID_ENV', 'staging'), // staging | production
            'username' => env('OYID_USERNAME'),
            'api_key' => env('OYID_API_KEY'),
        ],

        /*
        |----------------------------------------------------------------------
        | Tripay
        |----------------------------------------------------------------------
        | Dokumentasi: https://tripay.co.id/developer
        | Dashboard: https://tripay.co.id/member/merchant
        */
        'tripay' => [
            'driver' => 'tripay',
            'environment' => env('TRIPAY_ENV', 'sandbox'), // sandbox | production
            'api_key' => env('TRIPAY_API_KEY'),
            'private_key' => env('TRIPAY_PRIVATE_KEY'),
            'merchant_code' => env('TRIPAY_MERCHANT_CODE'),
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
