# Midtrans Complete Usage Guide

Panduan ini fokus ke penggunaan driver `midtrans` pada package `aliziodev/payid` dari setup, semua API yang tersedia, sampai webhook.

## 1) Prasyarat

- `composer require aliziodev/payid`
- `composer require aliziodev/payid-midtrans`
- Publish config:

```bash
php artisan vendor:publish --tag=payid-config
```

## 2) Konfigurasi

### 2.1 Config driver

Contoh di `config/payid.php`:

```php
'drivers' => [
    'midtrans' => [
        'driver' => 'midtrans',
        'environment' => env('MIDTRANS_ENV', 'sandbox'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'order_prefix' => env('MIDTRANS_ORDER_PREFIX', ''),
        'endpoints' => [
            'snap_base_url' => env('MIDTRANS_SNAP_URL'),
            'core_base_url' => env('MIDTRANS_CORE_URL'),
        ],
    ],
],
```

### 2.2 ENV

```env
PAYID_DEFAULT_DRIVER=midtrans

MIDTRANS_ENV=sandbox
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
MIDTRANS_MERCHANT_ID=Gxxxxxxx
MIDTRANS_ORDER_PREFIX=
```

## 3) Pola pemanggilan

Gunakan facade `PayId`:

```php
use Illuminate\Support\Facades\PayId;

// pakai default driver dari config
PayId::charge($request);

// atau eksplisit
PayId::driver('midtrans')->charge($request);
```

## 4) Semua API pembayaran Midtrans via PayID

### 4.1 Charge (Snap)

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\Enums\PaymentChannel;

$response = PayId::driver('midtrans')->charge(ChargeRequest::make([
    'merchant_order_id' => 'ORDER-1001',
    'amount' => 150000,
    'currency' => 'IDR',
    'channel' => PaymentChannel::Qris,
    'customer' => [
        'name' => 'Alizio',
        'email' => 'budi@example.com',
        'phone' => '08123456789',
    ],
    'items' => [
        [
            'id' => 'SKU-1',
            'name' => 'Produk A',
            'quantity' => 1,
            'price' => 150000,
        ],
    ],
    'success_url' => 'https://example.com/payment/success',
    'failure_url' => 'https://example.com/payment/failed',
    'metadata' => [
        'source' => 'web-checkout',
    ],
]));

$paymentUrl = $response->paymentUrl;
```

### 4.2 Direct charge (Core API)

```php
$response = PayId::driver('midtrans')->directCharge(ChargeRequest::make([
    'merchant_order_id' => 'ORDER-1002',
    'amount' => 200000,
    'currency' => 'IDR',
    'channel' => PaymentChannel::VaBca,
    'customer' => [
        'name' => 'Citra',
        'email' => 'citra@example.com',
    ],
]));
```

### 4.3 Status

```php
$status = PayId::driver('midtrans')->status('ORDER-1001');
$canonical = $status->status->value;
```

### 4.4 Refund

```php
use Aliziodev\PayId\DTO\RefundRequest;

$refund = PayId::driver('midtrans')->refund(RefundRequest::make([
    'merchant_order_id' => 'ORDER-1001',
    'amount' => 50000,
    'reason' => 'Partial return',
    'refund_key' => 'refund-order-1001-1',
]));
```

### 4.5 Cancel / Expire / Approve / Deny

```php
$cancelled = PayId::driver('midtrans')->cancel('ORDER-1001');
$expired = PayId::driver('midtrans')->expire('ORDER-1001');
$approved = PayId::driver('midtrans')->approve('ORDER-1001');
$denied = PayId::driver('midtrans')->deny('ORDER-1001');
```

## 5) Semua API subscription Midtrans via PayID

### 5.1 Create subscription

```php
use Aliziodev\PayId\DTO\SubscriptionRequest;
use Aliziodev\PayId\Enums\SubscriptionInterval;

$created = PayId::driver('midtrans')->createSubscription(SubscriptionRequest::make([
    'subscription_id' => 'SUB-1001',
    'name' => 'Gold Monthly',
    'amount' => 99000,
    'currency' => 'IDR',
    'token' => 'saved_token_or_account_token',
    'interval' => SubscriptionInterval::Month,
    'interval_count' => 1,
    'max_cycle' => 12,
    'metadata' => [
        'tenant' => 'tenant-a',
    ],
]));
```

### 5.2 Get / Update / Pause / Resume / Cancel subscription

```php
use Aliziodev\PayId\DTO\UpdateSubscriptionRequest;

$detail = PayId::driver('midtrans')->getSubscription($created->providerSubscriptionId);

$updated = PayId::driver('midtrans')->updateSubscription(UpdateSubscriptionRequest::make([
    'provider_subscription_id' => $created->providerSubscriptionId,
    'name' => 'Gold Monthly Updated',
    'amount' => 109000,
    'interval_count' => 1,
]));

$paused = PayId::driver('midtrans')->pauseSubscription($created->providerSubscriptionId);
$resumed = PayId::driver('midtrans')->resumeSubscription($created->providerSubscriptionId);
$cancelled = PayId::driver('midtrans')->cancelSubscription($created->providerSubscriptionId);
```

## 6) API khusus Midtrans (GoPay account linking)

Fitur ini adalah extension API pada `MidtransDriver` (bukan method umum di `PayIdManager`).

```php
use Aliziodev\PayIdMidtrans\DTO\GopayAccountLinkRequest;
use Aliziodev\PayIdMidtrans\MidtransDriver;

/** @var MidtransDriver $driver */
$driver = PayId::driver('midtrans')->getDriver();

$linked = $driver->linkGopayAccount(new GopayAccountLinkRequest(
    phoneNumber: '08123456789',
    countryCode: '62',
    redirectUrl: 'https://example.com/gopay/callback',
));

$account = $driver->getGopayAccount($linked->accountId);
$driver->unlinkGopayAccount($linked->accountId);
```

## 7) Capability guard (best practice)

Sebelum memanggil API opsional, cek capability:

```php
use Aliziodev\PayId\Enums\Capability;

if (PayId::driver('midtrans')->supports(Capability::Refund)) {
    // aman panggil refund
}
```

## 8) Webhook end-to-end

### 8.1 Route webhook bawaan

PayID otomatis register route berikut saat service provider aktif:

- `POST /payid/webhook/{driver}`
- route name: `payid.webhook`

Contoh endpoint untuk Midtrans:

- `https://yourdomain.com/payid/webhook/midtrans`

### 8.2 Konfigurasi route prefix dan middleware

Atur di `config/payid.php`:

```php
'webhook' => [
    'route_prefix' => env('PAYID_WEBHOOK_PREFIX', 'payid'),
    'route_middleware' => [],
],
```

Contoh jika ingin endpoint jadi `/api/payments/webhook/midtrans`:

```env
PAYID_WEBHOOK_PREFIX=api/payments
```

Verifikasi route setelah ubah config:

```bash
php artisan route:list --name=payid.webhook
```

Jika Anda menambahkan middleware auth, pastikan Midtrans tetap bisa mengakses endpoint callback publik Anda.

### 8.3 Setup URL webhook di Midtrans Dashboard

Set notification URL ke endpoint final aplikasi Anda:

- Sandbox: gunakan domain staging/public tunnel.
- Production: gunakan domain production HTTPS.
- URL: `https://yourdomain.com/{PAYID_WEBHOOK_PREFIX}/webhook/midtrans`

Untuk local testing, gunakan tunnel (misalnya ngrok) agar callback Midtrans bisa menjangkau endpoint lokal.

### 8.4 Alur verifikasi, parsing, dan response

Pipeline webhook PayID:

1. Verifikasi signature oleh driver Midtrans.
2. Parse payload ke `NormalizedWebhook`.
3. Dispatch hook event internal.
4. Return HTTP response dari pipeline.

Kemungkinan response endpoint:

- `200 OK` dengan body `OK`: webhook valid dan terproses.
- `401` dengan body `Webhook signature verification failed.`: signature gagal.
- `422` dengan body `Webhook payload parsing failed.`: payload tidak bisa diparse.

### 8.5 Hooks/event internal yang bisa Anda konsumsi

Event yang didispatch oleh pipeline webhook:

- `Aliziodev\PayId\Events\WebhookReceived`
- `Aliziodev\PayId\Events\WebhookVerificationFailed`
- `Aliziodev\PayId\Events\WebhookParsingFailed`

Selain itu, Anda juga bisa memanfaatkan event bisnis PayID (misalnya `PaymentCharged`, `PaymentStatusChecked`) untuk hook di alur non-webhook.

### 8.6 Listener aplikasi

```php
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Enums\PaymentStatus;
use Illuminate\Support\Facades\Event;

Event::listen(WebhookReceived::class, function (WebhookReceived $event): void {
    $webhook = $event->webhook;

    if ($webhook->provider !== 'midtrans') {
        return;
    }

    if ($webhook->status === PaymentStatus::Paid) {
        // Tandai order paid berdasarkan merchantOrderId
    }

    if ($webhook->status->isRefunded()) {
        // Proses state refund
    }
});
```

Contoh listener untuk menangani kegagalan verifikasi/parsing:

```php
use Aliziodev\PayId\Events\WebhookParsingFailed;
use Aliziodev\PayId\Events\WebhookVerificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

Event::listen(WebhookVerificationFailed::class, function (WebhookVerificationFailed $event): void {
    Log::warning('Midtrans webhook verification failed', [
        'driver' => $event->driver,
        'ip' => $event->request->ip(),
    ]);
});

Event::listen(WebhookParsingFailed::class, function (WebhookParsingFailed $event): void {
    Log::error('Midtrans webhook parsing failed', [
        'driver' => $event->driver,
        'error' => $event->exception->getMessage(),
    ]);
});
```

### 8.7 Integrasi ledger (opsional tapi direkomendasikan)

Jika `aliziodev/payid-transactions` terpasang, PayID otomatis:

- merekam webhook event,
- update snapshot status transaksi,
- menandai hasil processing webhook.

## 9) Ringkasan fitur Midtrans

Didukung penuh:

- Charge (Snap)
- Direct charge (Core API)
- Status
- Refund
- Cancel / Expire / Approve / Deny
- Subscription lifecycle
- Webhook verification + parsing
- GoPay account linking (extension method driver)

## 10) Lihat juga

- Matriks fitur lintas driver: `docs/11-driver-feature-matrix.md`
- Quick reference extension API Midtrans: `docs/13-midtrans-extension-api-quick-reference.md`

## 11) Matriks scope fitur Midtrans dan cara pakai via PayID

Status pada tabel ini mengikuti kemampuan driver `payid-midtrans` saat ini.

| Scope fitur Midtrans | Dukungan di driver PayID Midtrans | Cara penggunaan via PayID | Catatan |
|---|---|---|---|
| Snap API | Supported | `PayId::driver('midtrans')->charge(ChargeRequest::make([...]))` | Untuk checkout URL/token berbasis Snap. |
| Core API | Supported | `directCharge`, `status`, `refund`, `cancel`, `expire`, `approve`, `deny` | Operasi transactional lifecycle via API terstandarisasi PayID. |
| Subscription | Supported | `createSubscription`, `getSubscription`, `updateSubscription`, `pauseSubscription`, `resumeSubscription`, `cancelSubscription` | Semua method tersedia di manager/facade PayID. |
| Snap-BI | Supported (driver-specific) | `PayId::driver('midtrans')->getDriver()->getSnapBiTransactionStatus(...)` | Extension method di `MidtransDriver`, bukan API manager umum. |
| Payment Link | Supported (driver-specific) | `createPaymentLink`, `getPaymentLink`, `deletePaymentLink` | Extension method di `MidtransDriver`, bukan API manager umum. |
| Balance | Supported (driver-specific) | `getBalanceMutation(currency, startTime, endTime)` | Extension method di `MidtransDriver`, bukan API manager umum. |
| Invoicing | Supported (driver-specific) | `createInvoice`, `getInvoice`, `voidInvoice` | Extension method di `MidtransDriver`, bukan API manager umum. |
| GoPay account linking | Supported (driver-specific) | `PayId::driver('midtrans')->getDriver()->linkGopayAccount(...)` | Fitur extension khusus kelas `MidtransDriver`, bukan API umum manager. |

### Contoh ringkas pemakaian per scope yang supported

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\RefundRequest;
use Aliziodev\PayId\DTO\SubscriptionRequest;
use Illuminate\Support\Facades\PayId;

// Snap API
$charge = PayId::driver('midtrans')->charge(ChargeRequest::make([
    'merchant_order_id' => 'ORDER-9001',
    'amount' => 150000,
    'currency' => 'IDR',
]));

// Core API
$status = PayId::driver('midtrans')->status('ORDER-9001');
$refund = PayId::driver('midtrans')->refund(RefundRequest::make([
    'merchant_order_id' => 'ORDER-9001',
    'amount' => 50000,
]));

// Subscription
$subscription = PayId::driver('midtrans')->createSubscription(SubscriptionRequest::make([
    'subscription_id' => 'SUB-9001',
    'name' => 'Monthly Plan',
    'amount' => 99000,
    'currency' => 'IDR',
    'token' => 'saved_token',
    'interval' => \Aliziodev\PayId\Enums\SubscriptionInterval::Month,
]));

/** @var \Aliziodev\PayIdMidtrans\MidtransDriver $driver */
$driver = PayId::driver('midtrans')->getDriver();

// Snap-BI
$b2bStatus = $driver->getSnapBiTransactionStatus('ORDER-9001');

// Payment Link
$paymentLink = $driver->createPaymentLink([
    'transaction_details' => [
        'order_id' => 'ORDER-LINK-9001',
        'gross_amount' => 150000,
    ],
]);

// Balance mutation
$balanceMutation = $driver->getBalanceMutation('IDR', '2026-04-01 00:00:00', '2026-04-14 23:59:59');

// Invoicing
$invoice = $driver->createInvoice([
    'external_id' => 'INV-9001',
    'payer_email' => 'budi@example.com',
    'description' => 'Invoice test',
    'amount' => 200000,
]);
```

### Catatan implementasi berdasarkan referensi resmi Midtrans

#### A) Invoicing endpoint dan payload

Referensi resmi:

- Create Invoice: https://docs.midtrans.com/reference/create-invoice
- Get Invoice: https://docs.midtrans.com/reference/get-invoice
- Void Invoice: https://docs.midtrans.com/reference/void-invoice
- JSON Objects (Invoice): https://docs.midtrans.com/reference/json-objects-1

Poin penting saat integrasi:

- `createInvoice(...)` membutuhkan payload yang mengikuti object Midtrans (misalnya `customer_details`, `item_details`, `payment_type`, `payment_link` / `virtual_accounts`, `amount`).
- Field ID invoice dari Midtrans adalah `id`, sedangkan id bisnis Anda tetap sebaiknya disimpan di `order_id` / `invoice_number` / `reference` sesuai desain aplikasi.
- Status invoice yang umum: `draft`, `pending`, `expired`, `overdue`, `paid`, `voided`.
- `voidInvoice(...)` hanya valid untuk invoice yang masih memenuhi eligibility Midtrans (umumnya `pending` atau `overdue`).

#### B) Handling notifications untuk invoice/payment

Referensi resmi:

- Handling Notifications: https://docs.midtrans.com/reference/handling-notifications

Poin penting saat integrasi dengan PayID webhook pipeline:

- Notification Midtrans tetap dikirim ke endpoint webhook PayID (`POST /payid/webhook/midtrans` atau prefix yang Anda konfigurasi).
- Signature harus divalidasi sebelum update status order (sudah ditangani oleh driver Midtrans + pipeline PayID).
- Untuk transaksi invoice, Midtrans dapat mengirim `metadata.midtrans_invoice_id`; gunakan ini untuk korelasi ke data invoice internal jika diperlukan.
- Tetap gunakan `merchant_order_id` sebagai korelasi utama order lintas driver, dan gunakan `metadata.midtrans_invoice_id` sebagai korelasi sekunder.

Contoh listener untuk membaca `midtrans_invoice_id`:

```php
use Aliziodev\PayId\Events\WebhookReceived;
use Illuminate\Support\Facades\Event;

Event::listen(WebhookReceived::class, function (WebhookReceived $event): void {
    if ($event->webhook->provider !== 'midtrans') {
        return;
    }

    $invoiceId = data_get($event->webhook->rawPayload, 'metadata.midtrans_invoice_id');

    // Gunakan $invoiceId jika Anda menyimpan relasi invoice Midtrans terpisah.
});
```

#### C) Mapping field Midtrans Invoice ke model/data aplikasi (rekomendasi)

| Midtrans field | Sumber API | Rekomendasi mapping di aplikasi/PayID context | Catatan |
|---|---|---|---|
| `order_id` | Create/Get Invoice, Notification | `merchant_order_id` (primary business key) | Gunakan sebagai key lintas driver. |
| `id` (invoice id) | Create/Get Invoice | `provider_invoice_id` atau simpan di metadata internal | Bukan pengganti `merchant_order_id`. |
| `invoice_number` | Create/Get Invoice | `invoice_number` (opsional) | Cocok untuk nomor invoice display. |
| `status` (`draft/pending/expired/overdue/paid/voided`) | Get Invoice | state invoice internal | Pisahkan status invoice vs payment bila perlu. |
| `gross_amount` | Create/Get Invoice/Notification (`gross_amount`) | amount transaksi | Validasi konsistensi ke order amount internal. |
| `payment_link_url` | Create/Get Invoice | `payment_url`/checkout URL | Relevan saat `payment_type=payment_link`. |
| `virtual_accounts[]` | Create/Get Invoice | data VA di payment instruction | Relevan saat `payment_type=virtual_account`. |
| `pdf_url` | Create/Get Invoice | `invoice_pdf_url` | Untuk kebutuhan download/arsip invoice. |
| `payment_type` | Create/Get Invoice/Notification (`payment_type`) | channel/payment method | Bisa dipetakan ke enum channel internal. |
| `transaction_id` | Notification | `provider_transaction_id` | Penting untuk rekonsiliasi provider-level. |
| `metadata.midtrans_invoice_id` | Notification | `provider_invoice_id` (secondary correlation) | Gunakan sebagai secondary key korelasi invoice. |

Prinsip yang disarankan:

- Primary correlation key lintas driver: `merchant_order_id`.
- Secondary provider key untuk Midtrans Invoice: `provider_invoice_id` dari `id` atau `metadata.midtrans_invoice_id`.
