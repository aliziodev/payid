# Xendit Complete Usage Guide

Panduan ini fokus ke penggunaan driver `xendit` pada package `aliziodev/payid` secara lengkap untuk fitur yang tersedia saat ini (MVP), termasuk webhook.

## 1) Prasyarat

- `composer require aliziodev/payid`
- `composer require aliziodev/payid-xendit`
- Publish config:

```bash
php artisan vendor:publish --tag=payid-config
```

## 2) Konfigurasi

Contoh di `config/payid.php`:

```php
'drivers' => [
    'xendit' => [
        'driver' => 'xendit',
        'environment' => env('XENDIT_ENV', 'test'),
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'public_key' => env('XENDIT_PUBLIC_KEY'),
        'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        'invoice_duration_seconds' => env('XENDIT_INVOICE_DURATION', 86400),
    ],
],
```

Contoh `.env`:

```env
PAYID_DEFAULT_DRIVER=xendit

XENDIT_ENV=test
XENDIT_SECRET_KEY=xnd_development_xxx
XENDIT_PUBLIC_KEY=xnd_public_xxx
XENDIT_WEBHOOK_TOKEN=your-callback-token
XENDIT_INVOICE_DURATION=86400
```

## 3) Pola pemanggilan

```php
use Illuminate\Support\Facades\PayId;

PayId::driver('xendit')->charge($request);
```

## 4) Semua API Xendit via PayID

### 4.1 Charge (Invoice API)

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\Enums\PaymentChannel;

$response = PayId::driver('xendit')->charge(ChargeRequest::make([
    'merchant_order_id' => 'ORDER-2001',
    'amount' => 175000,
    'currency' => 'IDR',
    'channel' => PaymentChannel::Qris,
    'customer' => [
        'name' => 'Dina Pratiwi',
        'email' => 'dina@example.com',
    ],
    'description' => 'Checkout ORDER-2001',
    'success_url' => 'https://example.com/payment/success',
    'failure_url' => 'https://example.com/payment/failed',
    'metadata' => [
        'source' => 'mobile-app',
    ],
]));

$paymentUrl = $response->paymentUrl;
```

### 4.2 Status

```php
$status = PayId::driver('xendit')->status('ORDER-2001');

$canonicalStatus = $status->status->value;
// created|pending|authorized|paid|failed|expired|cancelled|refunded|partially_refunded
```

### 4.3 Refund

```php
use Aliziodev\PayId\DTO\RefundRequest;

$refund = PayId::driver('xendit')->refund(RefundRequest::make([
    'merchant_order_id' => 'ORDER-2001',
    'amount' => 50000,
    'reason' => 'Customer requested refund',
    'refund_key' => 'refund-order-2001-1',
]));
```

### 4.4 PaymentMethod (driver extension)

Scope PaymentMethod diexpose sebagai extension method di kelas `XenditDriver`.

```php
/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();

$paymentMethod = $driver->createPaymentMethod([
    'type' => 'EWALLET',
    'reusability' => 'ONE_TIME_USE',
]);

$paymentMethodDetail = $driver->getPaymentMethod((string) $paymentMethod['id']);
```

### 4.5 PaymentRequest (driver extension)

```php
/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();

$paymentRequest = $driver->createPaymentRequest([
    'reference_id' => 'PR-ORDER-2001',
    'amount' => 175000,
    'currency' => 'IDR',
    'payment_method_id' => 'pm-xxxx',
], 'idem-pr-2001');

$paymentRequestDetail = $driver->getPaymentRequest((string) $paymentRequest['id']);
```

### 4.6 Customer (driver extension)

```php
/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();

$customer = $driver->createCustomer([
    'reference_id' => 'CUST-2001',
    'individual_detail' => [
        'given_names' => 'Budi',
    ],
], 'idem-cust-2001');

$customerDetail = $driver->getCustomer((string) $customer['id']);
```

### 4.7 Payout (driver extension)

```php
/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();

$payout = $driver->createPayout([
    'reference_id' => 'PAYOUT-2001',
    'channel_code' => 'ID_BCA',
    'channel_properties' => [
        'account_number' => '1234567890',
        'account_holder_name' => 'Alizio',
    ],
    'amount' => 100000,
    'currency' => 'IDR',
    'description' => 'Vendor settlement',
], 'idem-payout-2001');

$payoutDetail = $driver->getPayout((string) $payout['id']);
```

### 4.8 Balance (driver extension)

```php
/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();

$balance = $driver->getBalance('CASH', 'IDR');
```

### 4.9 Transaction (driver extension)

```php
/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();

$transaction = $driver->getTransaction('txn_123e4567-e89b-42d3-a456-426614174000');

$transactions = $driver->listTransactions('ORDER-2001', 20);
```

Catatan: format `transaction_id` mengikuti pattern SDK Xendit (`txn_<uuid-v4>`).

## 5) Capability map untuk Xendit

Didukung:

- `Capability::Charge`
- `Capability::Refund`
- `Capability::Status`
- `Capability::WebhookVerification`
- `Capability::WebhookParsing`

Belum didukung pada driver Xendit saat ini:

- `direct_charge`
- `cancel`
- `expire`
- `approve`
- `deny`
- seluruh API subscription

Gunakan guard sebelum memanggil API opsional:

```php
use Aliziodev\PayId\Enums\Capability;

if (PayId::driver('xendit')->supports(Capability::Refund)) {
    // pada versi ini sudah true
}
```

## 6) Webhook end-to-end

### 6.1 Route webhook bawaan

PayID otomatis register route berikut:

- `POST /payid/webhook/{driver}`
- route name: `payid.webhook`

Contoh endpoint untuk Xendit:

- `https://yourdomain.com/payid/webhook/xendit`

### 6.2 Konfigurasi route prefix dan middleware

Atur di `config/payid.php`:

```php
'webhook' => [
    'route_prefix' => env('PAYID_WEBHOOK_PREFIX', 'payid'),
    'route_middleware' => [],
],
```

Contoh jika endpoint ingin menjadi `/api/payments/webhook/xendit`:

```env
PAYID_WEBHOOK_PREFIX=api/payments
```

Cek route aktif:

```bash
php artisan route:list --name=payid.webhook
```

Jika menambah middleware auth/rate-limit, pastikan callback Xendit tetap dapat mengakses endpoint.

### 6.3 Setup webhook URL di Xendit Dashboard

Set callback URL ke endpoint final aplikasi Anda:

- Test mode: domain staging/public tunnel.
- Live mode: domain production HTTPS.
- URL: `https://yourdomain.com/{PAYID_WEBHOOK_PREFIX}/webhook/xendit`

Pastikan callback token di dashboard sama dengan `XENDIT_WEBHOOK_TOKEN`.

### 6.4 Verifikasi webhook

Driver Xendit memverifikasi header callback token:

- `X-CALLBACK-TOKEN` harus sama dengan `XENDIT_WEBHOOK_TOKEN`.

Jika token invalid, endpoint mengembalikan `401` dengan body `Webhook signature verification failed.`.

### 6.5 Parsing webhook

Driver menormalisasi payload invoice/payment callback ke `NormalizedWebhook`, termasuk:

- provider
- merchant order id (`external_id` atau `data.reference_id`)
- provider transaction id
- canonical payment status
- amount/currency
- channel (jika tersedia)

### 6.6 Response contract endpoint webhook

Kemungkinan respons route webhook:

- `200 OK` dengan body `OK`: verifikasi dan parsing sukses.
- `401` dengan body `Webhook signature verification failed.`: token/signature gagal.
- `422` dengan body `Webhook payload parsing failed.`: payload tidak valid.

### 6.7 Hooks/event internal yang bisa Anda konsumsi

Event pipeline webhook:

- `Aliziodev\PayId\Events\WebhookReceived`
- `Aliziodev\PayId\Events\WebhookVerificationFailed`
- `Aliziodev\PayId\Events\WebhookParsingFailed`

Event bisnis lain dari PayID bisa tetap dipakai sebagai hook lintas flow pembayaran.

### 6.8 Listener aplikasi

```php
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Enums\PaymentStatus;
use Illuminate\Support\Facades\Event;

Event::listen(WebhookReceived::class, function (WebhookReceived $event): void {
    $webhook = $event->webhook;

    if ($webhook->provider !== 'xendit') {
        return;
    }

    if ($webhook->status === PaymentStatus::Paid) {
        // mark order paid
    } elseif ($webhook->status === PaymentStatus::Expired) {
        // mark order expired
    }
});
```

Contoh listener untuk audit error webhook:

```php
use Aliziodev\PayId\Events\WebhookParsingFailed;
use Aliziodev\PayId\Events\WebhookVerificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

Event::listen(WebhookVerificationFailed::class, function (WebhookVerificationFailed $event): void {
    Log::warning('Xendit webhook verification failed', [
        'driver' => $event->driver,
        'ip' => $event->request->ip(),
    ]);
});

Event::listen(WebhookParsingFailed::class, function (WebhookParsingFailed $event): void {
    Log::error('Xendit webhook parsing failed', [
        'driver' => $event->driver,
        'error' => $event->exception->getMessage(),
    ]);
});
```

### 6.9 Integrasi ledger (opsional)

Jika `aliziodev/payid-transactions` aktif, webhook dan status snapshot akan otomatis tercatat oleh pipeline PayID.

## 7) Ringkasan fitur Xendit

Tersedia saat ini:

- Charge (Invoice)
- Refund
- Status
- Webhook verification + parsing

Driver extension (di luar API manager standar):

- PaymentMethod (`createPaymentMethod`, `getPaymentMethod`)

Roadmap berikutnya (jika diaktifkan di driver):

- Operasi payment lanjutan
- Subscription lifecycle

## 8) Lihat juga

- Matriks fitur lintas driver: `docs/11-driver-feature-matrix.md`

## 9) Matriks scope fitur Xendit dan cara pakai via PayID

Status pada tabel ini mengikuti kemampuan driver `payid-xendit` saat ini.

| Scope fitur Xendit | Dukungan di driver PayID Xendit | Cara penggunaan via PayID | Catatan |
|---|---|---|---|
| Invoice | Supported | `PayId::driver('xendit')->charge(ChargeRequest::make([...]))`, `status(...)` | Scope utama MVP driver saat ini. |
| PaymentRequest | Supported (driver-specific) | `PayId::driver('xendit')->getDriver()->createPaymentRequest([...])` | Extension method di `XenditDriver`, bukan API manager umum. |
| PaymentMethod | Supported (driver-specific) | `PayId::driver('xendit')->getDriver()->createPaymentMethod([...])` | Extension method di `XenditDriver`, bukan API manager umum. |
| Refund | Supported | `PayId::driver('xendit')->refund(RefundRequest::make([...]))` | Sudah tersedia via capability `Refund`. |
| Balance | Supported (driver-specific) | `PayId::driver('xendit')->getDriver()->getBalance('CASH', 'IDR')` | Extension method di `XenditDriver`, bukan API manager umum. |
| Transaction | Supported (driver-specific) | `PayId::driver('xendit')->getDriver()->getTransaction('txn_...')` | Extension method di `XenditDriver`, bukan API manager umum. |
| Customer | Supported (driver-specific) | `PayId::driver('xendit')->getDriver()->createCustomer([...])` | Extension method di `XenditDriver`, bukan API manager umum. |
| Payout | Supported (driver-specific) | `PayId::driver('xendit')->getDriver()->createPayout([...], 'idempotency-key')` | Extension method di `XenditDriver`, bukan API manager umum. |

### Contoh ringkas pemakaian scope yang sudah didukung

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\RefundRequest;
use Illuminate\Support\Facades\PayId;

$charge = PayId::driver('xendit')->charge(ChargeRequest::make([
    'merchant_order_id' => 'ORDER-9901',
    'amount' => 175000,
    'currency' => 'IDR',
]));

$status = PayId::driver('xendit')->status('ORDER-9901');

$refund = PayId::driver('xendit')->refund(RefundRequest::make([
    'merchant_order_id' => 'ORDER-9901',
    'amount' => 50000,
]));

/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();
$paymentMethod = $driver->createPaymentMethod([
    'type' => 'EWALLET',
    'reusability' => 'ONE_TIME_USE',
]);

$paymentRequest = $driver->createPaymentRequest([
    'reference_id' => 'PR-ORDER-9901',
    'amount' => 175000,
    'currency' => 'IDR',
    'payment_method_id' => (string) ($paymentMethod['id'] ?? ''),
], 'idem-pr-9901');

$customer = $driver->createCustomer([
    'reference_id' => 'CUST-9901',
    'individual_detail' => [
        'given_names' => 'Dina',
    ],
], 'idem-cust-9901');

$payout = $driver->createPayout([
    'reference_id' => 'PAYOUT-9901',
    'channel_code' => 'ID_BCA',
    'channel_properties' => [
        'account_number' => '1234567890',
        'account_holder_name' => 'Dina Pratiwi',
    ],
    'amount' => 100000,
    'currency' => 'IDR',
], 'idem-payout-9901');

$balance = $driver->getBalance('CASH', 'IDR');

$transaction = $driver->getTransaction('txn_123e4567-e89b-42d3-a456-426614174000');
$transactions = $driver->listTransactions('ORDER-9901', 20);
```

### Guard pattern yang direkomendasikan

Sebelum memanggil fitur opsional, selalu cek capability agar aman lintas driver:

```php
use Aliziodev\PayId\Enums\Capability;
use Illuminate\Support\Facades\PayId;

if (PayId::driver('xendit')->supports(Capability::Refund)) {
    // refund sudah tersedia
}
```
