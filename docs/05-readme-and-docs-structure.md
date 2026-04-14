# PayID — README & Docs Structure
> Rancangan Struktur Dokumentasi Lengkap
> Version: 1.0.0-draft | Date: 2026-04-13

---

## 1. Filosofi Dokumentasi PayID

Dokumentasi bukan afterthought — dokumentasi adalah bagian dari public API PayID.

**Prinsip:**
- Dokumentasi ditulis bersamaan dengan kode, bukan setelah selesai
- Setiap public API harus terdokumentasi sebelum dianggap selesai
- README harus cukup untuk onboarding developer baru tanpa bantuan pembuat
- Dokumentasi internal (arsitektur, keputusan desain) sama pentingnya dengan dokumentasi pengguna
- Setiap driver harus memiliki dokumentasi sendiri yang lengkap

---

## 2. Struktur Folder Dokumentasi

```
payid/
├── README.md                         ← Pintu masuk utama package
├── CHANGELOG.md                      ← Riwayat perubahan per versi
├── UPGRADE.md                        ← Panduan upgrade antar major version
├── CONTRIBUTING.md                   ← Panduan kontribusi
├── SECURITY.md                       ← Kebijakan pelaporan security issue
│
└── docs/
    ├── getting-started/
    │   ├── installation.md           ← Instalasi package
    │   ├── quick-start.md            ← Langsung jalan dalam 5 menit
    │   └── configuration.md         ← Penjelasan semua config option
    │
    ├── usage/
    │   ├── creating-payment.md       ← Cara membuat transaksi
    │   ├── checking-status.md        ← Cara cek status transaksi
    │   ├── handling-webhooks.md      ← Setup dan penanganan webhook
    │   ├── refund-cancel-expire.md   ← Refund, cancel, expire
    │   ├── switching-drivers.md      ← Ganti driver saat runtime
    │   └── error-handling.md        ← Cara menangani exception
    │
    ├── drivers/
    │   ├── overview.md              ← Gambaran umum sistem driver
    │   ├── midtrans.md              ← Dokumentasi driver Midtrans
    │   ├── xendit.md                ← Dokumentasi driver Xendit
    │   └── custom-driver.md        ← Panduan membuat driver sendiri
    │
    ├── testing/
    │   ├── overview.md              ← Strategi testing PayID
    │   ├── fake-driver.md           ← Cara memakai PayIdFake
    │   └── assertions.md           ← Daftar assertion helpers
    │
    └── internals/
        ├── architecture.md         ← Arsitektur internal PayID
        ├── adr/                    ← Architecture Decision Records
        │   ├── 001-core-driver-separation.md
        │   ├── 002-capability-based-contracts.md
        │   ├── 003-immutable-dto.md
        │   └── 004-webhook-pipeline.md
        └── driver-authoring.md    ← Panduan teknis membuat driver
```

---

## 3. README.md (Root)

Berikut adalah rancangan isi `README.md` utama package:

---

```markdown
# PayID

> Unified Laravel Payment Orchestrator for Indonesian Payment Gateways

PayID adalah package Laravel yang menyatukan berbagai payment gateway Indonesia
(Midtrans, Xendit, DOKU, iPaymu, dan lainnya) dalam satu API yang konsisten.
Integrasikan sekali, gunakan provider mana saja.

---

## Daftar Isi

- [Requirements](#requirements)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Penggunaan Dasar](#penggunaan-dasar)
- [Driver yang Tersedia](#driver-yang-tersedia)
- [Testing](#testing)
- [Dokumentasi Lengkap](#dokumentasi-lengkap)
- [Kontribusi](#kontribusi)
- [Lisensi](#lisensi)

---

## Requirements

- PHP 8.2+
- Laravel 11.x atau 12.x

---

## Instalasi

Install PayID core:

```bash
composer require aliziodev/payid
```

Install driver provider yang diinginkan:

```bash
# Midtrans
composer require aliziodev/payid-midtrans

# Xendit
composer require aliziodev/payid-xendit
```

Publish konfigurasi:

```bash
php artisan vendor:publish --tag=payid-config
```

---

## Konfigurasi

Tambahkan credential provider di `.env`:

```env
PAYID_DEFAULT_DRIVER=midtrans

MIDTRANS_ENV=sandbox
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
```

Lihat dokumentasi lengkap di [docs/getting-started/configuration.md](docs/getting-started/configuration.md).

---

## Penggunaan Dasar

### Membuat Transaksi

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\CustomerData;
use Aliziodev\PayId\Enums\PaymentChannel;
use Illuminate\Support\Facades\PayId;

$response = PayId::charge(ChargeRequest::make([
    'merchant_order_id' => 'ORDER-001',
    'amount'            => 150000,
    'currency'          => 'IDR',
    'channel'           => PaymentChannel::Qris,
    'customer'          => [
        'name'  => 'Alizio',
        'email' => 'budi@example.com',
    ],
]));

// Redirect atau tampilkan QR
return redirect($response->paymentUrl);
```

### Cek Status Transaksi

```php
$status = PayId::status('ORDER-001');

if ($status->status->isSuccessful()) {
    // Tandai order sebagai dibayar
}
```

### Menangani Webhook

Webhook route sudah terdaftar otomatis di `/payid/webhook/{driver}`.

Tambahkan URL webhook di dashboard provider, misalnya:
`https://yourdomain.com/payid/webhook/midtrans`

Tangkap event di `AppServiceProvider` atau Event Listener:

```php
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Enums\PaymentStatus;

Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
    $webhook = $event->webhook;

    if ($webhook->status === PaymentStatus::Paid) {
        Order::markAsPaid($webhook->merchantOrderId);
    }
});
```

### Ganti Driver Saat Runtime

```php
$response = PayId::driver('xendit')->charge($request);
```

---

## Driver yang Tersedia

| Driver | Package | Status |
|--------|---------|--------|
| Midtrans | `aliziodev/payid-midtrans` | Stable |
| Xendit   | `aliziodev/payid-xendit`   | Stable |
| DOKU     | `aliziodev/payid-doku`     | Coming Soon |
| iPaymu   | `aliziodev/payid-ipaymu`   | Coming Soon |

---

## Testing

PayID menyediakan fake driver untuk testing tanpa hit API nyata:

```php
use Aliziodev\PayId\Testing\PayIdFake;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\Enums\PaymentStatus;

PayId::fake();

PayId::fakeCharge(ChargeResponse::make([
    'provider_name'            => 'midtrans',
    'provider_transaction_id'  => 'TRX-001',
    'merchant_order_id'        => 'ORDER-001',
    'status'                   => PaymentStatus::Pending,
    'payment_url'              => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/xxxx',
    'raw_response'             => [],
]));

// Jalankan kode yang memanggil PayId::charge(...)

PayId::assertCharged();
PayId::assertDriverUsed('midtrans');
```

Lihat [docs/testing/fake-driver.md](docs/testing/fake-driver.md) untuk panduan lengkap.

---

## Dokumentasi Lengkap

- [Instalasi](docs/getting-started/installation.md)
- [Konfigurasi](docs/getting-started/configuration.md)
- [Quick Start](docs/getting-started/quick-start.md)
- [Membuat Transaksi](docs/usage/creating-payment.md)
- [Cek Status](docs/usage/checking-status.md)
- [Webhook](docs/usage/handling-webhooks.md)
- [Error Handling](docs/usage/error-handling.md)
- [Custom Driver](docs/drivers/custom-driver.md)
- [Testing](docs/testing/overview.md)

---

## Kontribusi

Lihat [CONTRIBUTING.md](CONTRIBUTING.md) untuk panduan berkontribusi.

---

## Lisensi

MIT License. Lihat [LICENSE](LICENSE) untuk detail.
```

---

## 4. docs/getting-started/installation.md

```markdown
# Instalasi

## Requirements

- PHP 8.2 atau lebih baru
- Laravel 11.x atau 12.x
- Composer 2.x

## Install Core Package

```bash
composer require aliziodev/payid
```

Package ini menggunakan Laravel auto-discovery, sehingga `PayIdServiceProvider`
dan `PayId` facade terdaftar otomatis.

## Install Driver

Install driver untuk provider yang ingin digunakan:

```bash
# Midtrans
composer require aliziodev/payid-midtrans

# Xendit
composer require aliziodev/payid-xendit

# Keduanya sekaligus
composer require aliziodev/payid-midtrans aliziodev/payid-xendit
```

## Publish Konfigurasi

```bash
php artisan vendor:publish --tag=payid-config
```

File `config/payid.php` akan dibuat di project Anda.

## Verifikasi Instalasi

```bash
php artisan about
```

Pastikan `PayId` tercantum di bagian Packages.
```

---

## 5. docs/getting-started/quick-start.md

```markdown
# Quick Start — 5 Menit

Panduan ini mengasumsikan Anda sudah menyelesaikan [instalasi](installation.md).

## Step 1 — Konfigurasi .env

```env
PAYID_DEFAULT_DRIVER=midtrans
MIDTRANS_ENV=sandbox
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxx
```

## Step 2 — Buat Transaksi

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\CustomerData;
use Aliziodev\PayId\Enums\PaymentChannel;
use Illuminate\Support\Facades\PayId;

$response = PayId::charge(ChargeRequest::make([
    'merchant_order_id' => uniqid('ORDER-'),
    'amount'            => 100000,
    'currency'          => 'IDR',
    'channel'           => PaymentChannel::Qris,
    'customer'          => [
        'name'  => 'Andi Wijaya',
        'email' => 'andi@example.com',
        'phone' => '08123456789',
    ],
    'description'       => 'Pembayaran Order #001',
    'callback_url'      => route('payment.callback'),
]));

// $response->paymentUrl berisi URL redirect ke halaman pembayaran
// $response->qrString  berisi string QR jika channel adalah QRIS
// $response->vaNumber  berisi nomor VA jika channel adalah Virtual Account
// $response->status    adalah PaymentStatus enum (biasanya ::Pending)
// $response->rawResponse berisi raw response dari provider
```

## Step 3 — Setup Webhook

Tambahkan URL berikut ke dashboard sandbox Midtrans:
```
https://yourdomain.com/payid/webhook/midtrans
```

Untuk development lokal, gunakan tool seperti Ngrok atau Expose:
```bash
ngrok http 8000
# Gunakan URL ngrok: https://xxxx.ngrok.io/payid/webhook/midtrans
```

## Step 4 — Tangkap Event Webhook

```php
// app/Providers/AppServiceProvider.php
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Enums\PaymentStatus;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
        $webhook = $event->webhook;

        match ($webhook->status) {
            PaymentStatus::Paid      => Order::markAsPaid($webhook->merchantOrderId),
            PaymentStatus::Expired   => Order::markAsExpired($webhook->merchantOrderId),
            PaymentStatus::Cancelled => Order::markAsCancelled($webhook->merchantOrderId),
            default                  => null,
        };
    });
}
```

## Step 5 — Cek Status (Opsional)

```php
$status = PayId::status('ORDER-001');
echo $status->status->value; // 'paid', 'pending', dll
```

Selesai. Anda sudah menerima pembayaran pertama via PayID.
```

---

## 6. docs/getting-started/configuration.md

```markdown
# Konfigurasi

File konfigurasi PayID ada di `config/payid.php` setelah publish.

## Default Driver

```php
'default' => env('PAYID_DEFAULT_DRIVER', 'midtrans'),
```

Driver yang digunakan jika tidak ditentukan secara eksplisit.
Nilai harus sesuai dengan key di array `drivers`.

## Default Currency

```php
'currency' => env('PAYID_DEFAULT_CURRENCY', 'IDR'),
```

Mata uang default untuk `ChargeRequest` jika `currency` tidak diisi.

## Konfigurasi Driver

```php
'drivers' => [
    'midtrans' => [
        'driver'      => 'midtrans',    // wajib, nama driver
        'environment' => 'sandbox',     // sandbox | production
        'server_key'  => env('MIDTRANS_SERVER_KEY'),
        'client_key'  => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    ],
],
```

Anda bisa mendefinisikan lebih dari satu konfigurasi driver, bahkan dua
konfigurasi untuk provider yang sama (misalnya dua akun Midtrans berbeda):

```php
'drivers' => [
    'midtrans_store_a' => [
        'driver'     => 'midtrans',
        'server_key' => env('MIDTRANS_STORE_A_KEY'),
    ],
    'midtrans_store_b' => [
        'driver'     => 'midtrans',
        'server_key' => env('MIDTRANS_STORE_B_KEY'),
    ],
],
```

Gunakan dengan: `PayId::driver('midtrans_store_b')->charge($request)`

## HTTP Settings

```php
'http' => [
    'timeout'        => 30,     // detik
    'retry_times'    => 1,      // jumlah retry saat gagal
    'retry_delay_ms' => 500,    // jeda antar retry (milidetik)
],
```

## Webhook Settings

```php
'webhook' => [
    'route_prefix'    => 'payid',      // URL prefix: /payid/webhook/{driver}
    'route_middleware'=> [],            // tambahkan middleware jika perlu
    'queue'           => false,        // true = proses webhook via queue
    'queue_name'      => 'default',
    'queue_connection'=> null,
],
```

## Logging Settings

```php
'logging' => [
    'enabled'        => true,
    'channel'        => null,   // null = gunakan default Laravel log channel
    'mask_sensitive' => true,   // mask server_key, card data, dll di log
],
```

## Multi-Tenant Credential Resolver

Untuk aplikasi multi-tenant, Anda dapat men-override credential resolver:

```php
// Di AppServiceProvider::boot()
PayId::resolveCredentialsUsing(function (string $driver, Request $request): array {
    $tenant = app(TenantManager::class)->current();
    return $tenant->paymentCredentials($driver);
});
```
```

---

## 7. docs/usage/handling-webhooks.md

```markdown
# Menangani Webhook

## Bagaimana Webhook Bekerja di PayID

PayID mendaftarkan route webhook secara otomatis:

```
POST /payid/webhook/{driver}
```

Contoh URL untuk masing-masing provider:
- Midtrans: `https://yourdomain.com/payid/webhook/midtrans`
- Xendit:   `https://yourdomain.com/payid/webhook/xendit`

Saat webhook masuk, PayID akan:
1. Verifikasi signature (jika provider mendukung)
2. Parse payload ke format standar
3. Dispatch event `WebhookReceived`
4. Return HTTP 200

## Menangkap Event Webhook

```php
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Enums\PaymentStatus;

Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
    $webhook = $event->webhook; // NormalizedWebhook

    // Data yang tersedia:
    // $webhook->provider            — nama provider ('midtrans', 'xendit', dll)
    // $webhook->merchantOrderId     — order ID yang Anda kirim saat charge
    // $webhook->providerTransactionId
    // $webhook->status              — PaymentStatus enum
    // $webhook->amount              — nominal transaksi
    // $webhook->currency
    // $webhook->channel             — PaymentChannel enum
    // $webhook->occurredAt          — Carbon timestamp
    // $webhook->signatureValid      — bool: apakah signature valid
    // $webhook->rawPayload          — raw payload dari provider
});
```

## Menggunakan Dedicated Listener

```php
// app/Listeners/HandlePaymentWebhook.php
namespace App\Listeners;

use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Enums\PaymentStatus;
use App\Models\Order;

class HandlePaymentWebhook
{
    public function handle(WebhookReceived $event): void
    {
        $webhook = $event->webhook;
        $order   = Order::findByOrderId($webhook->merchantOrderId);

        if (!$order) {
            return;
        }

        match ($webhook->status) {
            PaymentStatus::Paid      => $order->markAsPaid(),
            PaymentStatus::Expired   => $order->markAsExpired(),
            PaymentStatus::Cancelled => $order->markAsCancelled(),
            default                  => null,
        };
    }
}
```

Daftarkan di `EventServiceProvider`:

```php
protected $listen = [
    WebhookReceived::class => [
        HandlePaymentWebhook::class,
    ],
];
```

## Mengecualikan Webhook dari CSRF

Tambahkan route webhook ke daftar exclude CSRF di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'payid/webhook/*',
    ]);
})
```

## Webhook via Queue

Untuk menghindari timeout, aktifkan queue processing di konfigurasi:

```php
// config/payid.php
'webhook' => [
    'queue'       => true,
    'queue_name'  => 'webhooks',
],
```

Pastikan queue worker berjalan:

```bash
php artisan queue:work --queue=webhooks
```

## Menangani Webhook Error

Event-event berikut di-dispatch saat terjadi error:

```php
use Aliziodev\PayId\Events\WebhookVerificationFailed;
use Aliziodev\PayId\Events\WebhookParsingFailed;

Event::listen(WebhookVerificationFailed::class, function ($event) {
    Log::warning('Webhook verification failed', ['driver' => $event->driver]);
});

Event::listen(WebhookParsingFailed::class, function ($event) {
    Log::error('Webhook parsing failed', [
        'driver' => $event->driver,
        'error'  => $event->exception->getMessage(),
    ]);
});
```

## Idempotency

Provider dapat mengirim webhook yang sama lebih dari sekali. Selalu implementasikan
pengecekan idempotency di listener Anda:

```php
if ($order->isPaid()) {
    return; // sudah diproses sebelumnya
}
```
```

---

## 8. docs/usage/error-handling.md

```markdown
# Error Handling

## Exception Hierarchy

Semua exception PayID extend `PayIdException`:

```
PayIdException
├── ConfigurationException
│   ├── MissingDriverConfigException
│   └── InvalidCredentialException
├── DriverException
│   ├── DriverNotFoundException
│   └── DriverResolutionException
├── UnsupportedCapabilityException
├── ProviderException
│   ├── ProviderApiException        ← Error dari API provider (4xx, 5xx)
│   ├── ProviderResponseException   ← Response tidak dapat di-parse
│   └── ProviderNetworkException    ← Timeout, connection error
├── WebhookException
│   ├── WebhookVerificationException
│   └── WebhookParsingException
└── PayloadMappingException
```

## Menangani Exception

### Catch-all

```php
use Aliziodev\PayId\Exceptions\PayIdException;

try {
    $response = PayId::charge($request);
} catch (PayIdException $e) {
    Log::error('Payment failed', ['error' => $e->getMessage()]);
    // Handle error
}
```

### Catch Spesifik

```php
use Aliziodev\PayId\Exceptions\ProviderApiException;
use Aliziodev\PayId\Exceptions\ProviderNetworkException;
use Aliziodev\PayId\Exceptions\UnsupportedCapabilityException;

try {
    $response = PayId::charge($request);
} catch (ProviderNetworkException $e) {
    // Retry atau tampilkan pesan "coba beberapa saat lagi"
    return back()->with('error', 'Gagal terhubung ke payment provider. Coba lagi.');

} catch (ProviderApiException $e) {
    // Error dari provider (credential salah, order ID duplikat, dll)
    Log::error('Provider API error', [
        'driver'      => $e->getDriver(),
        'http_status' => $e->getHttpStatus(),
    ]);
    return back()->with('error', 'Pembayaran gagal diproses.');

} catch (UnsupportedCapabilityException $e) {
    // Driver tidak mendukung aksi yang diminta
    return back()->with('error', 'Metode pembayaran ini tidak didukung.');
}
```

## Membedakan System Error vs Business Error

| Exception | Tipe | Tindakan |
|---|---|---|
| `ProviderNetworkException` | System | Retry, coba lagi nanti |
| `ProviderApiException` | Business/System | Log + notify team |
| `MissingDriverConfigException` | System | Fix konfigurasi |
| `UnsupportedCapabilityException` | Business | Tampilkan pesan ke user |
| `WebhookVerificationException` | Security | Log + alert |

## Akses Raw Error

```php
try {
    $response = PayId::charge($request);
} catch (ProviderApiException $e) {
    // Raw response dari provider tersedia untuk debugging
    $rawResponse = $e->getRawResponse();
    Log::debug('Raw provider error', $rawResponse);
}
```

Raw response tidak otomatis diekspos ke end-user untuk alasan keamanan.
```

---

## 9. docs/drivers/custom-driver.md

```markdown
# Membuat Driver Sendiri

Panduan ini menjelaskan cara membuat driver PayID untuk payment provider
yang belum ada driver resminya.

## Step 1 — Buat Package Baru

```bash
mkdir payid-myprovider && cd payid-myprovider
composer init
```

## Step 2 — Tambahkan PayID sebagai Dependency

```json
{
    "require": {
        "aliziodev/payid": "^1.0"
    }
}
```

## Step 3 — Implementasikan DriverInterface

```php
namespace MyVendor\PayIdMyProvider;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Contracts\HasCapabilities;
use Aliziodev\PayId\Contracts\SupportsCharge;
use Aliziodev\PayId\Contracts\SupportsStatus;
use Aliziodev\PayId\Contracts\SupportsWebhookVerification;
use Aliziodev\PayId\Contracts\SupportsWebhookParsing;
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\Enums\Capability;
use Illuminate\Http\Request;

class MyProviderDriver implements
    DriverInterface,
    SupportsCharge,
    SupportsStatus,
    SupportsWebhookVerification,
    SupportsWebhookParsing
{
    use HasCapabilities;

    public function getName(): string
    {
        return 'myprovider';
    }

    public function getCapabilities(): array
    {
        return [
            Capability::Charge,
            Capability::Status,
            Capability::WebhookVerification,
            Capability::WebhookParsing,
        ];
    }

    public function charge(ChargeRequest $request): ChargeResponse
    {
        // Map ChargeRequest ke payload provider Anda
        // Kirim HTTP request ke API provider
        // Map response ke ChargeResponse
    }

    public function status(string $merchantOrderId): StatusResponse
    {
        // Kirim HTTP request ke API status provider
        // Map response ke StatusResponse
    }

    public function verifyWebhook(Request $request): bool
    {
        // Verifikasi signature webhook
        // Gunakan $request->getContent() untuk raw body
        return hash_equals(
            $expectedSignature,
            $request->header('X-Signature')
        );
    }

    public function parseWebhook(Request $request): NormalizedWebhook
    {
        $payload = json_decode($request->getContent(), true);

        return new NormalizedWebhook(
            provider: $this->getName(),
            merchantOrderId: $payload['order_id'],
            status: $this->mapStatus($payload['transaction_status']),
            signatureValid: true,
            rawPayload: $payload,
        );
    }

    private function mapStatus(string $providerStatus): PaymentStatus
    {
        return match ($providerStatus) {
            'settlement' => PaymentStatus::Paid,
            'pending'    => PaymentStatus::Pending,
            'expire'     => PaymentStatus::Expired,
            'cancel'     => PaymentStatus::Cancelled,
            'deny'       => PaymentStatus::Failed,
            default      => PaymentStatus::Pending,
        };
    }
}
```

## Step 4 — Daftarkan Driver ke PayID

```php
// Di ServiceProvider driver Anda
use Aliziodev\PayId\Managers\PayIdManager;

public function boot(): void
{
    $this->app->resolving(PayIdManager::class, function (PayIdManager $manager) {
        $manager->extend('myprovider', function (array $config) {
            return new MyProviderDriver($config);
        });
    });
}
```

## Step 5 — Konfigurasi di Aplikasi

```php
// config/payid.php
'drivers' => [
    'myprovider' => [
        'driver'  => 'myprovider',
        'api_key' => env('MYPROVIDER_API_KEY'),
    ],
],
```

## Step 6 — Tulis Test

```
tests/
├── Unit/
│   ├── MyProviderDriverTest.php
│   └── Mappers/
└── Fixtures/
    ├── webhook-paid.json
    └── charge-response.json
```

## Checklist Sebelum Publish

- [ ] Semua interface yang diklaim di `getCapabilities()` benar-benar diimplementasikan
- [ ] Webhook verification menggunakan raw body
- [ ] Semua exception di-wrap ke PayIdException
- [ ] Raw response selalu disertakan di DTO
- [ ] Unit test untuk semua mapper
- [ ] Fixture JSON untuk semua skenario webhook
- [ ] README menjelaskan cara setup dan environment variables
```

---

## 10. docs/testing/fake-driver.md

```markdown
# Testing dengan PayIdFake

PayID menyediakan fake driver agar Anda bisa menulis test tanpa hit API payment
provider yang nyata.

## Setup

```php
use Aliziodev\PayId\Testing\PayIdFake;
use Illuminate\Support\Facades\PayId;

// Di setUp() test Anda
PayId::fake();
```

## Konfigurasi Response

```php
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\Enums\PaymentStatus;

PayId::fakeCharge(ChargeResponse::make([
    'provider_name'           => 'midtrans',
    'provider_transaction_id' => 'TRX-FAKE-001',
    'merchant_order_id'       => 'ORDER-001',
    'status'                  => PaymentStatus::Pending,
    'payment_url'             => 'https://example.com/pay',
    'raw_response'            => [],
]));
```

## Assertions

```php
// Assert charge dipanggil tepat 1 kali
PayId::assertCharged();

// Assert charge dipanggil N kali
PayId::assertCharged(3);

// Assert tidak ada charge yang dilakukan
PayId::assertNothingCharged();

// Assert driver yang digunakan
PayId::assertDriverUsed('midtrans');

// Assert charge dengan kriteria tertentu
PayId::assertChargedWith(function (ChargeRequest $request) {
    return $request->amount === 150000
        && $request->channel === PaymentChannel::Qris;
});
```

## Contoh Test Lengkap

```php
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\Enums\PaymentStatus;
use Illuminate\Support\Facades\PayId;

class CheckoutControllerTest extends TestCase
{
    public function test_user_can_checkout(): void
    {
        PayId::fake();

        PayId::fakeCharge(ChargeResponse::make([
            'provider_name'           => 'midtrans',
            'provider_transaction_id' => 'TRX-001',
            'merchant_order_id'       => 'ORDER-001',
            'status'                  => PaymentStatus::Pending,
            'payment_url'             => 'https://sandbox.midtrans.com/pay',
            'raw_response'            => [],
        ]));

        $response = $this->post('/checkout', [
            'product_id' => 1,
            'quantity'   => 2,
        ]);

        $response->assertRedirect('https://sandbox.midtrans.com/pay');

        PayId::assertCharged();
        PayId::assertChargedWith(fn ($req) => $req->amount === 200000);
    }
}
```
```

---

## 11. CONTRIBUTING.md

```markdown
# Contributing to PayID

Terima kasih atas minat Anda untuk berkontribusi ke PayID.

## Cara Berkontribusi

### Bug Reports

Laporkan bug melalui GitHub Issues. Sertakan:
- Versi PayID, versi PHP, versi Laravel
- Langkah reproduksi yang jelas
- Expected vs actual behavior
- Log error jika ada

### Feature Requests

Diskusikan terlebih dahulu via GitHub Discussions sebelum membuat PR.

### Pull Requests

1. Fork repository
2. Buat branch dari `main`: `git checkout -b feature/nama-fitur`
3. Tulis test untuk perubahan Anda
4. Pastikan semua test lulus: `composer test`
5. Pastikan code style lulus: `composer lint`
6. Buat PR dengan deskripsi yang jelas

## Standard

### Code Style

PayID menggunakan Laravel Pint:

```bash
composer lint
```

### Static Analysis

```bash
composer analyse
```

### Testing

```bash
# Jalankan semua test
composer test

# Dengan coverage
composer test-coverage
```

## Driver Contributions

Jika Anda ingin membuat driver untuk provider baru:

1. Baca [docs/drivers/custom-driver.md](docs/drivers/custom-driver.md)
2. Buat repository terpisah dengan nama `payid-{provider}`
3. Pastikan memenuhi checklist di bagian akhir custom-driver guide
4. Informasikan ke maintainer untuk ditambahkan ke daftar driver komunitas

## Commit Messages

Gunakan format Conventional Commits:

```
feat: tambah support DANA di Xendit driver
fix: perbaiki signature verification Midtrans saat body kosong
docs: update webhook guide untuk setup di dashboard Xendit
test: tambah fixture webhook expired untuk Midtrans
```

## Versioning

PayID menggunakan Semantic Versioning (semver).
Diskusikan breaking changes di GitHub Discussions sebelum implementasi.
```

---

## 12. CHANGELOG.md (Template)

```markdown
# Changelog

Semua perubahan signifikan pada project ini akan didokumentasikan di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
dan project ini mengikuti [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- ...

### Changed
- ...

### Fixed
- ...

### Deprecated
- ...

### Removed
- ...

---

## [1.0.0] - TBD

### Added
- Core package foundation (contracts, DTO, enums, manager, webhook pipeline)
- Midtrans driver dengan support QRIS, GoPay, VA BCA/BNI/BRI/Mandiri
- Xendit driver dengan support Invoice, VA, QRIS, E-wallet
- PayIdFake untuk testing tanpa hit API nyata
- Structured logging dengan masking data sensitif
- Event dispatching: WebhookReceived, PaymentCharged, PaymentStatusChecked
- Exception hierarchy yang konsisten
- Auto-discovery untuk Laravel 11 dan 12

---

## [0.2.0-beta] - TBD

### Added
- Midtrans driver (beta)
- Xendit driver (beta)
- Contract tests untuk semua driver

---

## [0.1.0-alpha] - TBD

### Added
- Core package scaffold
- Contracts, DTO, Enums, Exceptions, Events
- PayIdManager
- WebhookProcessor
- PayIdFake
- Laravel service provider dan facade
```

---

## 13. Ringkasan Dokumen

| File | Tujuan | Audience |
|---|---|---|
| `README.md` | Pintu masuk, overview, quick start | Semua developer |
| `docs/getting-started/installation.md` | Cara install | Developer baru |
| `docs/getting-started/quick-start.md` | Langsung jalan | Developer baru |
| `docs/getting-started/configuration.md` | Semua config option | Developer yang setup |
| `docs/usage/creating-payment.md` | API charge detail | Developer yang build |
| `docs/usage/checking-status.md` | API status | Developer yang build |
| `docs/usage/handling-webhooks.md` | Webhook setup dan event | Developer yang build |
| `docs/usage/error-handling.md` | Exception handling | Developer yang build |
| `docs/drivers/overview.md` | Sistem driver | Developer yang evaluasi |
| `docs/drivers/midtrans.md` | Spesifik Midtrans | Developer yang pakai Midtrans |
| `docs/drivers/xendit.md` | Spesifik Xendit | Developer yang pakai Xendit |
| `docs/drivers/custom-driver.md` | Buat driver sendiri | Contributor / advanced user |
| `docs/testing/fake-driver.md` | Testing dengan fake | Developer yang write test |
| `docs/testing/assertions.md` | Daftar assertions | Developer yang write test |
| `docs/internals/architecture.md` | Arsitektur internal | Contributor / maintainer |
| `docs/internals/adr/` | Keputusan desain | Contributor / maintainer |
| `CONTRIBUTING.md` | Cara kontribusi | Contributor |
| `CHANGELOG.md` | Riwayat perubahan | Semua |
| `UPGRADE.md` | Panduan upgrade | Developer yang upgrade |
