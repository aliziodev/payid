# iPaymu Complete Usage Guide

Panduan ini fokus ke penggunaan driver `ipaymu` pada package `aliziodev/payid` untuk fitur yang tersedia saat ini.

## 1) Prasyarat

- `composer require aliziodev/payid`
- `composer require aliziodev/payid-ipaymu`
- Publish config:

```bash
php artisan vendor:publish --tag=payid-config
```

## 2) Konfigurasi

Contoh di `config/payid.php`:

```php
'drivers' => [
    'ipaymu' => [
        'driver' => 'ipaymu',
        'environment' => env('IPAYMU_ENV', 'sandbox'),
        'va' => env('IPAYMU_VA'),
        'api_key' => env('IPAYMU_API_KEY'),

        // optional override
        'base_url' => env('IPAYMU_BASE_URL'),
        'timeout' => env('IPAYMU_TIMEOUT', 30),

        // webhook hardening
        'webhook_verification_enabled' => (bool) env('IPAYMU_WEBHOOK_VERIFY', false),
        'webhook_token' => env('IPAYMU_WEBHOOK_TOKEN'),
        'webhook_signature_key' => env('IPAYMU_WEBHOOK_SIGNATURE_KEY'),

        // endpoint override jika diperlukan
        'payment_path' => '/api/v2/payment',
        'direct_payment_path' => '/api/v2/payment/direct',
        'payment_channel_path' => '/api/v2/payment-channel',
        'transaction_path' => '/api/v2/transaction',
        'balance_path' => '/api/v2/balance',
        'history_path' => '/api/v2/history',
    ],
],
```

Contoh `.env`:

```env
PAYID_DEFAULT_DRIVER=ipaymu

IPAYMU_ENV=sandbox
IPAYMU_VA=0000000000000000
IPAYMU_API_KEY=your-api-key

IPAYMU_WEBHOOK_VERIFY=false
IPAYMU_WEBHOOK_TOKEN=
IPAYMU_WEBHOOK_SIGNATURE_KEY=
```

## 3) Pola pemanggilan

```php
use Illuminate\Support\Facades\PayId;

PayId::driver('ipaymu')->charge($request);
```

## 4) API iPaymu via PayID

### 4.1 Charge

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\Enums\PaymentChannel;
use Illuminate\Support\Facades\PayId;

$response = PayId::driver('ipaymu')->charge(ChargeRequest::make([
    'merchant_order_id' => 'ORDER-3001',
    'amount' => 150000,
    'currency' => 'IDR',
    'channel' => PaymentChannel::Qris,
    'customer' => [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'phone' => '081234567890',
    ],
    'description' => 'Checkout ORDER-3001',
    'success_url' => 'https://example.com/payment/success',
    'failure_url' => 'https://example.com/payment/failed',
    'callback_url' => 'https://example.com/payid/webhook/ipaymu',
]));

$paymentUrl = $response->paymentUrl;
```

### 4.2 Status

```php
$status = PayId::driver('ipaymu')->status('ORDER-3001');

$canonicalStatus = $status->status->value;
// created|pending|authorized|paid|failed|expired|cancelled|refunded|partially_refunded
```

### 4.3 Webhook

Route webhook sudah otomatis dari core PayID:

- `POST /{prefix}/webhook/ipaymu`

Prefix default adalah `payid`, jadi default route menjadi:

- `POST /payid/webhook/ipaymu`

Event yang dipakai di aplikasi sama dengan driver lain:

- `WebhookReceived`
- `WebhookVerificationFailed`
- `WebhookParsingFailed`

Contoh listener:

```php
use Aliziodev\PayId\Events\WebhookReceived;
use Illuminate\Support\Facades\Event;

Event::listen(WebhookReceived::class, function (WebhookReceived $event): void {
    if ($event->webhook->provider !== 'ipaymu') {
        return;
    }

    $orderId = $event->webhook->merchantOrderId;
    $status = $event->webhook->status;

    // update order internal
});
```

### 4.4 Driver extension: direct payment, redirect payment page, payment channels, check balance, history transaction, callback params

```php
/** @var \Aliziodev\PayIdIpaymu\IpaymuDriver $driver */
$driver = PayId::driver('ipaymu')->getDriver();

$directPayment = $driver->directPayment([
    'amount' => 50000,
    'buyerName' => 'Budi Santoso',
]);

$redirectPayment = $driver->redirectPayment([
    'referenceId' => 'ORDER-3001',
    'amount' => 150000,
]);

$channels = $driver->paymentChannels();
$channelsList = $driver->listPaymentChannels();

$balance = $driver->checkBalance();

$history = $driver->historyTransaction([
    'limit' => 20,
]);

$callbackParams = $driver->callbackParams([
    'referenceId' => 'ORDER-3001',
    'transactionId' => 'TRX-3001',
    'status' => 'pending',
]);
```

## 5) Matriks scope fitur iPaymu dan cara pakai via PayID

| Scope fitur iPaymu | Dukungan di driver PayID iPaymu | Cara penggunaan via PayID | Catatan |
|---|---|---|---|
| Payment | Supported | `PayId::driver('ipaymu')->charge(...)` | Menghasilkan `ChargeResponse` standar PayID. |
| Redirect payment (iPaymu Payment Page) | Supported | `getDriver()->redirectPayment(...)` atau `charge(...)` | Redirect URL tersedia dari response (`payment_url`). |
| Check transaction | Supported | `PayId::driver('ipaymu')->status(...)` | Menghasilkan `StatusResponse` standar PayID. |
| Webhook verification | Supported | pipeline `POST /{prefix}/webhook/ipaymu` | Aktifkan strict verification via config `webhook_verification_enabled=true`. |
| Webhook parsing | Supported | pipeline `WebhookReceived` | Dinormalisasi ke `NormalizedWebhook`. |
| Callback params (success, pending, expired) | Supported (driver-specific) | `getDriver()->callbackParams(...)` | Helper normalisasi callback payload. |
| Direct payment | Supported (driver-specific) | `getDriver()->directPayment(...)` | Extension method, bukan API manager umum. |
| List payment channels | Supported (driver-specific) | `getDriver()->listPaymentChannels(...)` atau `paymentChannels(...)` | Extension method untuk channel discovery. |
| Check balance | Supported (driver-specific) | `getDriver()->checkBalance(...)` | Extension method, bukan API manager umum. |
| History transaction | Supported (driver-specific) | `getDriver()->historyTransaction(...)` | Extension method, bukan API manager umum. |
| Refund / Cancel / Expire | Not yet | - | Belum diexpose di driver iPaymu saat ini. |

## 6) Catatan integrasi

Lihat juga:

- `docs/15-ipaymu-extension-api-quick-reference.md`

- Gunakan `merchant_order_id` sebagai primary business key lintas driver.
- Untuk multi-driver app, selalu cek capability sebelum memanggil API opsional:

```php
use Aliziodev\PayId\Enums\Capability;

if (PayId::driver('ipaymu')->supports(Capability::Refund)) {
    // currently false untuk iPaymu
}
```

- Untuk endpoint iPaymu yang berubah atau berbeda akun, gunakan `base_url` dan path override di config.
- Setelah kredensial production aktif, set `IPAYMU_ENV=production`.
