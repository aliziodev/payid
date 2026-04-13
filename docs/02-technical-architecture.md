# PayID — Technical Architecture Document
> Version: 1.0.0-draft | Date: 2026-04-13

Referensi diagram detail end-to-end tersedia di:

- `docs/diagram/README.md`
- `docs/diagram/01-payid-complete-system-flow.md`

---

## 1. Gambaran Arsitektur Tingkat Tinggi

PayID dibagi menjadi tiga lapisan yang terpisah secara boundary namun terhubung melalui kontrak eksplisit:

```
┌─────────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER                        │
│  (Laravel app, controller, job, command, service class)      │
└───────────────────┬─────────────────────────────────────────┘
                    │ Public API (facade / container binding)
┌───────────────────▼─────────────────────────────────────────┐
│                   PAYID CORE PACKAGE                         │
│  contracts · DTO · enums · manager · webhook pipeline        │
│  events · exceptions · testing utilities · config           │
└────────┬──────────────────────────────┬──────────────────────┘
         │ DriverInterface               │ DriverInterface
┌────────▼────────┐            ┌────────▼────────┐
│  payid-midtrans │            │  payid-xendit   │
│  (driver pkg)   │            │  (driver pkg)   │
└─────────────────┘            └─────────────────┘
         ...                            ...
```

### Prinsip Lapisan

| Lapisan | Tanggung Jawab | Tidak Boleh |
|---|---|---|
| Application Layer | Memanggil PayID API, menangani event | Tahu detail provider |
| PayID Core | Orkestrasi, kontrak, normalisasi | Import detail provider |
| Driver Package | Implementasi provider-specific | Mengubah public API core |

---

## 2. PayID Core Package

### 2.1 Komponen Core

```
src/
├── Contracts/          — Interface publik (driver, capability, webhook)
├── DTO/                — Data Transfer Objects (immutable transport objects)
├── Enums/              — Enumerasi standar (status, channel, capability)
├── Exceptions/         — Hierarchy exception standar
├── Events/             — Event definitions (PaymentCharged, WebhookReceived, dll)
├── Managers/           — PayIdManager sebagai orchestrator utama
├── Webhooks/           — Webhook pipeline processor
├── Support/            — Utilities (Arr, Money, Signature, Http, Mapper)
├── Testing/            — Fake driver dan assertion helpers
├── Laravel/            — Service provider, facade, route registration
└── PayId.php           — Entry point / facade root class
```

### 2.2 PayIdManager

`PayIdManager` adalah pusat orkestrasi. Tanggung jawabnya:

- Membaca konfigurasi default driver
- Membuat instance driver melalui factory/resolver
- Menyediakan `driver($name)` untuk runtime switching
- Meneruskan method call ke driver yang aktif
- Mengecek capability sebelum meneruskan panggilan

```
PayIdManager
├── driver(string $name): DriverInterface
├── charge(ChargeRequest $request): ChargeResponse
├── status(string $orderId): StatusResponse
├── processWebhook(Request $request): WebhookResult
└── supports(Capability $capability): bool
```

`PayIdManager` tidak boleh berisi logic provider-specific. Semua routing diteruskan ke driver yang sesuai.

### 2.3 Driver Resolution

Driver di-resolve melalui mekanisme berikut:

1. Cek konfigurasi `payid.default` untuk default driver
2. Cari konfigurasi driver di `payid.drivers[$name]`
3. Resolve driver class dari container atau factory
4. Cache instance driver (per request, bukan singleton global)
5. Return driver yang sudah ter-resolve

Untuk multi-tenant atau multi-merchant, driver resolution dapat dikustomisasi melalui callable resolver yang dapat di-register di service provider aplikasi.

---

## 3. Driver Package

### 3.1 Struktur Driver Package

Setiap driver package (misalnya `aliziodev/payid-midtrans`) memiliki struktur:

```
payid-midtrans/
├── src/
│   ├── MidtransDriver.php          — Implementasi utama driver
│   ├── MidtransConfig.php          — Value object konfigurasi
│   ├── Mappers/
│   │   ├── ChargeRequestMapper.php — Map ChargeRequest → Midtrans payload
│   │   ├── ChargeResponseMapper.php— Map Midtrans response → ChargeResponse
│   │   └── StatusResponseMapper.php
│   ├── Webhooks/
│   │   ├── MidtransWebhookVerifier.php
│   │   └── MidtransWebhookParser.php
│   ├── Exceptions/
│   │   └── MidtransException.php
│   └── MidtransServiceProvider.php
├── config/
│   └── midtrans.php
├── tests/
│   ├── Unit/
│   └── Fixtures/
│       └── webhook-paid.json
└── composer.json
```

### 3.2 Tanggung Jawab Driver

Driver bertanggung jawab untuk:
- Mengimplementasikan contract yang relevan (SupportsCharge, SupportsStatus, dll.)
- Mendeklarasikan capabilities yang dimiliki
- Melakukan mapping dari DTO standar ke format payload provider
- Melakukan mapping dari response provider ke DTO standar
- Memverifikasi signature webhook
- Mengurai payload webhook ke NormalizedWebhook
- Melempar exception standar PayID (bukan exception provider-specific mentah)

Driver **tidak** bertanggung jawab untuk:
- Dispatching event (dilakukan oleh core)
- Logging (dilakukan oleh core/manager)
- HTTP routing webhook (dilakukan oleh core)

---

## 4. Contract Design

### 4.1 DriverInterface (Base Contract)

```php
interface DriverInterface
{
    public function getName(): string;
    public function getCapabilities(): array; // array of Capability enum
    public function supports(Capability $capability): bool;
}
```

Setiap driver **wajib** mengimplementasikan `DriverInterface`. Ini adalah kontrak minimum.

### 4.2 Capability-Based Contracts

Kontrak dipecah berdasarkan capability, bukan dalam satu interface besar:

```php
interface SupportsCharge
{
    public function charge(ChargeRequest $request): ChargeResponse;
}

interface SupportsStatus
{
    public function status(string $merchantOrderId): StatusResponse;
}

interface SupportsRefund
{
    public function refund(RefundRequest $request): RefundResponse;
}

interface SupportsCancel
{
    public function cancel(string $merchantOrderId): StatusResponse;
}

interface SupportsExpire
{
    public function expire(string $merchantOrderId): StatusResponse;
}

interface SupportsWebhookVerification
{
    public function verifyWebhook(Request $request): bool;
}

interface SupportsWebhookParsing
{
    public function parseWebhook(Request $request): NormalizedWebhook;
}
```

### 4.3 HasCapabilities Trait

Untuk kemudahan deklarasi capabilities di driver:

```php
trait HasCapabilities
{
    public function supports(Capability $capability): bool
    {
        return in_array($capability, $this->getCapabilities());
    }
}
```

### 4.4 Mengapa Kontrak Dipecah

| Alasan | Penjelasan |
|---|---|
| Jujur terhadap provider | Tidak semua provider mendukung refund, cancel, atau expire |
| Testable secara isolated | Setiap capability dapat diuji independen |
| Open/Closed Principle | Menambah capability baru tidak merusak kontrak lama |
| Type safety | Core dapat mengecek `instanceof SupportsRefund` sebelum memanggil |
| Documentation clarity | Dari interface, langsung jelas apa yang didukung driver |

---

## 5. Domain Standardization

### 5.1 PaymentStatus Enum

```
created           — Transaksi dibuat tapi belum ada aksi dari user
pending           — Menunggu pembayaran dari user
authorized        — Diauthorisasi (untuk kartu kredit, pre-capture)
paid              — Pembayaran berhasil dikonfirmasi
failed            — Pembayaran gagal
expired           — Transaksi kadaluarsa
cancelled         — Transaksi dibatalkan
refunded          — Dana sudah dikembalikan penuh
partially_refunded— Dana dikembalikan sebagian
```

### 5.2 PaymentChannel Enum

```
Virtual Account:
  va_bca | va_bni | va_bri | va_mandiri | va_permata | va_cimb | va_other

QRIS:
  qris

E-Wallet:
  gopay | shopeepay | ovo | dana | linkaja | sakuku

Card:
  credit_card | debit_card

Convenience Store:
  cstore_alfamart | cstore_indomaret

Bank Transfer:
  bank_transfer

Invoice / Payment Link:
  payment_link | invoice
```

### 5.3 Capability Enum

```
charge
status
refund
cancel
expire
webhook_verification
webhook_parsing
```

### 5.4 Prinsip Normalisasi

- Application layer **hanya** berinteraksi dengan enum standar ini
- Driver melakukan mapping dari nilai proprietary provider ke enum standar
- Jika tidak ada padanan yang tepat, gunakan nilai paling mendekati dan dokumentasikan
- `raw_response` selalu tersedia jika developer butuh nilai provider asli

---

## 6. DTO Design

### 6.1 Prinsip DTO

- **Immutable** — DTO dibuat sekali dan tidak diubah
- **No business logic** — DTO hanya transport object
- **Explicit fields** — Hindari array asosiatif; gunakan typed properties
- **Nullable untuk optional fields** — Field opsional menggunakan `?Type`
- **Named constructor (static factory)** — `ChargeRequest::make([...])`

### 6.2 ChargeRequest

```
required:
  string $merchantOrderId
  int $amount                 — dalam satuan terkecil (cents/rupiah)
  string $currency            — default 'IDR'
  PaymentChannel $channel
  CustomerData $customer

optional:
  ItemData[] $items
  string $description
  string $callbackUrl
  string $successUrl
  string $failureUrl
  Carbon $expiresAt
  array $metadata             — arbitrary data untuk keperluan app
```

### 6.3 ChargeResponse

```
required:
  string $providerName
  string $providerTransactionId
  string $merchantOrderId
  PaymentStatus $status
  array $rawResponse

optional:
  string $paymentUrl
  string $qrString
  string $vaNumber
  string $vaBankCode
  Carbon $expiresAt
```

### 6.4 StatusResponse

```
required:
  string $providerName
  string $providerTransactionId
  string $merchantOrderId
  PaymentStatus $status
  array $rawResponse

optional:
  Carbon $paidAt
  int $amount
  string $currency
  PaymentChannel $channel
```

### 6.5 NormalizedWebhook

```
required:
  string $provider
  string $merchantOrderId
  PaymentStatus $status
  bool $signatureValid
  array $rawPayload

optional:
  string $providerTransactionId
  string $eventType
  int $amount
  string $currency
  PaymentChannel $channel
  Carbon $occurredAt
```

### 6.6 CustomerData

```
required:
  string $name
  string $email

optional:
  string $phone
  string $address
```

### 6.7 ItemData

```
required:
  string $id
  string $name
  int $price
  int $quantity

optional:
  string $category
  string $merchantName
```

### 6.8 RefundRequest

```
required:
  string $merchantOrderId
  int $amount

optional:
  string $reason
  string $refundKey       — idempotency key
```

### 6.9 RefundResponse

```
required:
  string $providerName
  string $merchantOrderId
  string $refundId
  PaymentStatus $status
  array $rawResponse

optional:
  int $amount
  Carbon $refundedAt
```

---

## 7. Webhook Orchestration

### 7.1 Alur Lengkap

```
HTTP Request masuk ke /payid/webhook/{driver}
        │
        ▼
WebhookProcessor::handle(Request $request, string $driver)
        │
        ├─ 1. Identifikasi driver dari route parameter atau request body
        │
        ├─ 2. Resolve driver instance dari manager
        │
        ├─ 3. Verifikasi signature (jika driver SupportsWebhookVerification)
        │       └─ Jika gagal → log + return 401 + WebhookVerificationFailed event
        │
        ├─ 4. Parse payload (jika driver SupportsWebhookParsing)
        │       └─ Jika gagal → log + return 422 + WebhookParsingFailed event
        │
        ├─ 5. Normalisasi ke NormalizedWebhook
        │
        ├─ 6. Dispatch event WebhookReceived dengan NormalizedWebhook
        │       └─ Optional: dispatch ke queue
        │
        └─ 7. Return HTTP 200
```

### 7.2 Event yang Dilempar

```
WebhookReceived(NormalizedWebhook $webhook)
WebhookVerificationFailed(string $driver, Request $request)
WebhookParsingFailed(string $driver, Request $request, Throwable $e)
```

### 7.3 Prinsip Keamanan Webhook

- Signature verification **wajib** jika provider mendukung — tidak boleh di-skip
- Tolak request yang tidak bisa diverifikasi dengan response 401
- Gunakan raw request body untuk verifikasi signature (jangan JSON decode lebih awal)
- Simpan audit log: driver, IP, timestamp, verification result
- Support whitelist IP jika provider mendukung

### 7.4 Prinsip Reliability

- Webhook processor tidak boleh throw exception ke HTTP layer — selalu return response
- Semua error di-handle secara graceful dengan logging yang jelas
- Support queueable dispatch untuk menghindari timeout di webhook handler
- Idempotency: application layer bertanggung jawab menangani duplicate webhook

---

## 8. Exception Hierarchy

```
PayIdException (base)
├── ConfigurationException
│   ├── MissingDriverConfigException
│   └── InvalidCredentialException
├── DriverException
│   ├── DriverNotFoundException
│   └── DriverResolutionException
├── CapabilityException
│   └── UnsupportedCapabilityException
├── ProviderException
│   ├── ProviderApiException          — Error dari API provider (4xx, 5xx)
│   ├── ProviderResponseException     — Response tidak dapat diparsing
│   └── ProviderNetworkException      — Timeout, connection error
├── WebhookException
│   ├── WebhookVerificationException
│   └── WebhookParsingException
└── MappingException
    └── PayloadMappingException
```

### Prinsip Exception

- Semua exception PayID extend `PayIdException` — developer bisa catch semua dengan satu handler
- Exception harus membawa context yang cukup: driver name, order id, original exception
- Raw provider error disimpan sebagai property (bisa diakses jika butuh debug), tidak diekspos langsung ke end-user
- Exception harus dibedakan antara **system error** (network, config) dan **business error** (insufficient balance, invalid card)

---

## 9. Konfigurasi Package

### 9.1 Struktur config/payid.php

```php
return [
    'default' => env('PAYID_DEFAULT_DRIVER', 'midtrans'),

    'currency' => env('PAYID_DEFAULT_CURRENCY', 'IDR'),

    'drivers' => [
        'midtrans' => [
            'driver'       => 'midtrans',
            'environment'  => env('MIDTRANS_ENV', 'sandbox'),
            'server_key'   => env('MIDTRANS_SERVER_KEY'),
            'client_key'   => env('MIDTRANS_CLIENT_KEY'),
            'merchant_id'  => env('MIDTRANS_MERCHANT_ID'),
        ],
        'xendit' => [
            'driver'       => 'xendit',
            'environment'  => env('XENDIT_ENV', 'test'),
            'secret_key'   => env('XENDIT_SECRET_KEY'),
            'public_key'   => env('XENDIT_PUBLIC_KEY'),
            'webhook_token'=> env('XENDIT_WEBHOOK_VERIFICATION_TOKEN'),
        ],
    ],

    'http' => [
        'timeout'         => env('PAYID_HTTP_TIMEOUT', 30),
        'retry_times'     => env('PAYID_HTTP_RETRY', 1),
        'retry_delay_ms'  => 500,
    ],

    'webhook' => [
        'route_prefix'    => 'payid',
        'route_middleware'=> [],
        'queue'           => env('PAYID_WEBHOOK_QUEUE', false),
        'queue_name'      => env('PAYID_WEBHOOK_QUEUE_NAME', 'default'),
    ],

    'logging' => [
        'enabled'         => env('PAYID_LOGGING', true),
        'channel'         => env('PAYID_LOG_CHANNEL', 'default'),
        'mask_sensitive'  => true,
    ],

    // Optional: tenant-based credential resolver
    // 'credential_resolver' => null,
];
```

### 9.2 Prinsip Konfigurasi

- **Explicit over magic** — developer harus tahu apa yang dikonfigurasi
- **Environment-friendly** — semua credential lewat `.env`
- **Multi-driver native** — array `drivers` mendukung banyak provider sekaligus
- **Tidak terlalu nested** — maksimal 2 level nesting
- **Nama key konsisten** — snake_case di seluruh config

### 9.3 Multi-Tenant Credential Resolver

Untuk kasus multi-tenant, developer dapat men-register resolver:

```php
// Di AppServiceProvider
PayId::resolveCredentialsUsing(function (string $driver, Request $request): array {
    $tenant = $request->route('tenant');
    return TenantCredential::forDriver($driver, $tenant)->toArray();
});
```

---

## 10. Logging dan Observability

### 10.1 Minimum yang Harus Dilog

| Event | Level | Data |
|---|---|---|
| Driver resolved | DEBUG | driver name, source |
| Charge initiated | INFO | driver, order_id, channel, amount |
| Charge completed | INFO | driver, order_id, status |
| Charge failed | ERROR | driver, order_id, error summary |
| Webhook received | INFO | driver, order_id |
| Webhook verified | INFO | driver, result |
| Webhook parsed | INFO | driver, normalized_status |
| Exception thrown | ERROR | exception class, context |

### 10.2 Yang Tidak Boleh Dilog Mentah

- Secret key / API key
- Full card data
- Signature secret
- Personal data berlebihan (cukup log email saja, bukan full address)
- Raw request body yang mengandung credential

### 10.3 Masking Data Sensitif

PayID harus menyediakan utility untuk masking:

```
server_key   → "sk-mid-prod-****" (tampilkan 4 karakter pertama)
card_number  → "****-****-****-1234"
email        → "u***@example.com"
```

---

## 11. Testing Architecture

### 11.1 PayIdFake

Fake driver untuk testing aplikasi:

```php
PayId::fake([
    'charge' => ChargeResponse::make([...]),
    'status' => StatusResponse::make([...]),
]);

// Di test
$response = PayId::charge($request);
PayId::assertCharged();
PayId::assertDriverUsed('midtrans');
```

### 11.2 Lapisan Testing

| Layer | Tools | Scope |
|---|---|---|
| Unit | PHPUnit | DTO, mapper, enum, manager |
| Contract | PHPUnit | Semua driver harus lulus contract test yang sama |
| Integration | PHPUnit + HTTP fixture | Driver behavior dengan response fixture |
| Webhook | PHPUnit + JSON fixture | Verifikasi dan parsing webhook nyata |
| Fake-based | PHPUnit + PayIdFake | Testing aplikasi yang pakai PayID |

### 11.3 Assertion Helpers

```php
PayId::assertCharged(int $times = 1)
PayId::assertChargedWith(callable $callback)
PayId::assertDriverUsed(string $driver)
PayId::assertWebhookProcessed(int $times = 1)
PayId::assertStatusChecked(string $orderId)
PayId::assertNothingCharged()
```

---

## 12. Laravel Integration Layer

### 12.1 PayIdServiceProvider

```php
class PayIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payid.php', 'payid');
        $this->app->singleton(PayIdManager::class, fn($app) => new PayIdManager($app['config']));
        $this->app->alias(PayIdManager::class, 'payid');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/payid.php' => config_path('payid.php')], 'payid-config');
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
    }
}
```

### 12.2 Facade

```php
class PayId extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayIdManager::class;
    }
}
```

### 12.3 Auto-Discovery

```json
// composer.json
"extra": {
    "laravel": {
        "providers": ["Aliziodev\\PayId\\Laravel\\PayIdServiceProvider"],
        "aliases": {"PayId": "Aliziodev\\PayId\\Laravel\\Facades\\PayId"}
    }
}
```

### 12.4 Route Webhook

```php
// routes/webhooks.php
Route::post('/payid/webhook/{driver}', WebhookController::class)
    ->name('payid.webhook');
```

---

## 13. Dependency Management

### 13.1 Core Package Dependencies

```json
"require": {
    "php": "^8.2",
    "illuminate/contracts": "^11.0|^12.0",
    "illuminate/support": "^11.0|^12.0",
    "illuminate/http": "^11.0|^12.0"
}
```

Core hanya bergantung pada illuminate contracts dan support — tidak pada framework penuh atau package eksternal.

### 13.2 Driver Package Dependencies

Setiap driver bebas menambahkan dependency sesuai kebutuhan provider-nya:

```json
// payid-midtrans/composer.json
"require": {
    "aliziodev/payid": "^1.0",
    "midtrans/midtrans-php": "^2.5"
}
```

### 13.3 Mengapa Ini Penting

Dengan memisahkan dependency di driver, developer yang hanya memakai Xendit tidak perlu menanggung dependency SDK Midtrans. Ini menjaga instalasi tetap lean.

---

## 14. Backward Compatibility Policy

### 14.1 Public API

Komponen yang dianggap public API dan harus dijaga stabilitasnya:

- Semua `interface` di `Contracts/`
- Semua `enum` di `Enums/`
- Semua `DTO` di `DTO/`
- Method publik `PayIdManager`
- Method publik `PayId` facade
- Config keys di `payid.php`
- Route names

### 14.2 Internal API

Komponen yang boleh berubah tanpa dianggap breaking change:

- Semua `class` di `Support/`
- `WebhookProcessor` internal methods
- Private/protected methods di semua class

### 14.3 Versioning Rules

| Perubahan | Versi |
|---|---|
| Bug fix tanpa perubahan API | PATCH |
| Fitur baru tanpa breaking change | MINOR |
| Breaking change pada public API | MAJOR |
| Deprecation (dengan notice) | MINOR |
