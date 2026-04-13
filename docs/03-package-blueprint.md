# PayID — Package Blueprint
> Rancangan Detail Struktur, Kontrak, dan Implementasi
> Version: 1.0.0-draft | Date: 2026-04-13

---

## 1. Struktur Folder Lengkap

```
payid/                                    ← Root package
├── src/
│   ├── Contracts/                        ← Semua interface publik
│   │   ├── DriverInterface.php
│   │   ├── SupportsCharge.php
│   │   ├── SupportsStatus.php
│   │   ├── SupportsRefund.php
│   │   ├── SupportsCancel.php
│   │   ├── SupportsExpire.php
│   │   ├── SupportsWebhookVerification.php
│   │   ├── SupportsWebhookParsing.php
│   │   └── HasCapabilities.php           ← Trait helper
│   │
│   ├── DTO/                              ← Data Transfer Objects (immutable)
│   │   ├── ChargeRequest.php
│   │   ├── ChargeResponse.php
│   │   ├── StatusResponse.php
│   │   ├── RefundRequest.php
│   │   ├── RefundResponse.php
│   │   ├── CustomerData.php
│   │   ├── ItemData.php
│   │   └── NormalizedWebhook.php
│   │
│   ├── Enums/                            ← Enumerasi standar domain
│   │   ├── PaymentStatus.php
│   │   ├── PaymentChannel.php
│   │   ├── Capability.php
│   │   └── ProviderName.php              ← Opsional, untuk well-known providers
│   │
│   ├── Exceptions/                       ← Hierarchy exception standar
│   │   ├── PayIdException.php            ← Base exception
│   │   ├── ConfigurationException.php
│   │   ├── MissingDriverConfigException.php
│   │   ├── InvalidCredentialException.php
│   │   ├── DriverNotFoundException.php
│   │   ├── DriverResolutionException.php
│   │   ├── UnsupportedCapabilityException.php
│   │   ├── ProviderApiException.php
│   │   ├── ProviderResponseException.php
│   │   ├── ProviderNetworkException.php
│   │   ├── WebhookVerificationException.php
│   │   ├── WebhookParsingException.php
│   │   └── PayloadMappingException.php
│   │
│   ├── Events/                           ← Event definitions
│   │   ├── PaymentCharged.php
│   │   ├── PaymentStatusChecked.php
│   │   ├── WebhookReceived.php
│   │   ├── WebhookVerificationFailed.php
│   │   └── WebhookParsingFailed.php
│   │
│   ├── Managers/
│   │   └── PayIdManager.php              ← Orchestrator utama
│   │
│   ├── Factories/
│   │   └── DriverFactory.php             ← Factory untuk create driver instance
│   │
│   ├── Support/                          ← Internal utilities
│   │   ├── Arr.php                       ← Array helper spesifik PayID
│   │   ├── Money.php                     ← Konversi dan format nominal
│   │   ├── Signature.php                 ← Signature computation utilities
│   │   ├── Mask.php                      ← Masking data sensitif
│   │   └── Http/
│   │       └── PayIdHttpClient.php       ← Wrapper HTTP client standar
│   │
│   ├── Webhooks/
│   │   ├── WebhookProcessor.php          ← Pipeline processor
│   │   └── WebhookResult.php             ← Result object dari proses webhook
│   │
│   ├── Testing/
│   │   ├── PayIdFake.php                 ← Fake driver untuk testing
│   │   ├── FakeDriver.php                ← Implementasi driver palsu
│   │   └── Assertions/
│   │       └── PayIdAssertions.php       ← Trait assertion helpers
│   │
│   ├── Laravel/
│   │   ├── PayIdServiceProvider.php
│   │   ├── Facades/
│   │   │   └── PayId.php
│   │   └── Http/
│   │       └── Controllers/
│   │           └── WebhookController.php
│   │
│   └── PayId.php                         ← Entry point class
│
├── config/
│   └── payid.php                         ← Default configuration
│
├── routes/
│   └── webhooks.php                      ← Webhook route definition
│
├── tests/
│   ├── Unit/
│   │   ├── DTO/
│   │   ├── Enums/
│   │   ├── Managers/
│   │   └── Support/
│   ├── Integration/
│   │   └── WebhookProcessorTest.php
│   └── Pest.php / bootstrap.php
│
├── docs/
│   ├── 01-rnd-document.md
│   ├── 02-technical-architecture.md
│   ├── 03-package-blueprint.md
│   ├── 04-implementation-roadmap.md
│   └── 05-readme-and-docs-structure.md
│
├── composer.json
├── phpunit.xml
├── README.md
└── CHANGELOG.md
```

---

## 2. Contract Definitions

### 2.1 DriverInterface.php

```php
<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\Enums\Capability;

interface DriverInterface
{
    /**
     * Nama unik driver, digunakan sebagai identifier di config dan routing.
     * Contoh: 'midtrans', 'xendit', 'doku'
     */
    public function getName(): string;

    /**
     * Daftar capabilities yang didukung driver ini.
     *
     * @return Capability[]
     */
    public function getCapabilities(): array;

    /**
     * Cek apakah driver mendukung capability tertentu.
     */
    public function supports(Capability $capability): bool;
}
```

### 2.2 SupportsCharge.php

```php
<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;

interface SupportsCharge
{
    public function charge(ChargeRequest $request): ChargeResponse;
}
```

### 2.3 SupportsStatus.php

```php
<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\StatusResponse;

interface SupportsStatus
{
    public function status(string $merchantOrderId): StatusResponse;
}
```

### 2.4 SupportsWebhookVerification.php

```php
<?php

namespace Aliziodev\PayId\Contracts;

use Illuminate\Http\Request;

interface SupportsWebhookVerification
{
    /**
     * Verifikasi keaslian webhook dari provider.
     * Harus menggunakan raw request body, bukan parsed JSON.
     */
    public function verifyWebhook(Request $request): bool;
}
```

### 2.5 SupportsWebhookParsing.php

```php
<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\NormalizedWebhook;
use Illuminate\Http\Request;

interface SupportsWebhookParsing
{
    /**
     * Parse dan normalisasi payload webhook ke format standar PayID.
     */
    public function parseWebhook(Request $request): NormalizedWebhook;
}
```

### 2.6 HasCapabilities.php (Trait)

```php
<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\Enums\Capability;

trait HasCapabilities
{
    public function supports(Capability $capability): bool
    {
        return in_array($capability, $this->getCapabilities(), true);
    }
}
```

---

## 3. Enum Definitions

### 3.1 PaymentStatus.php

```php
<?php

namespace Aliziodev\PayId\Enums;

enum PaymentStatus: string
{
    case Created           = 'created';
    case Pending           = 'pending';
    case Authorized        = 'authorized';
    case Paid              = 'paid';
    case Failed            = 'failed';
    case Expired           = 'expired';
    case Cancelled         = 'cancelled';
    case Refunded          = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Failed,
            self::Expired,
            self::Cancelled,
            self::Refunded,
        ], true);
    }

    public function isSuccessful(): bool
    {
        return $this === self::Paid;
    }
}
```

### 3.2 PaymentChannel.php

```php
<?php

namespace Aliziodev\PayId\Enums;

enum PaymentChannel: string
{
    // Virtual Account
    case VaBca        = 'va_bca';
    case VaBni        = 'va_bni';
    case VaBri        = 'va_bri';
    case VaMandiri    = 'va_mandiri';
    case VaPermata    = 'va_permata';
    case VaCimb       = 'va_cimb';
    case VaOther      = 'va_other';

    // QRIS
    case Qris         = 'qris';

    // E-Wallet
    case Gopay        = 'gopay';
    case Shopeepay    = 'shopeepay';
    case Ovo          = 'ovo';
    case Dana         = 'dana';
    case Linkaja      = 'linkaja';
    case Sakuku       = 'sakuku';

    // Card
    case CreditCard   = 'credit_card';
    case DebitCard    = 'debit_card';

    // Convenience Store
    case CstoreAlfamart  = 'cstore_alfamart';
    case CstoreIndomaret = 'cstore_indomaret';

    // Bank Transfer
    case BankTransfer = 'bank_transfer';

    // Invoice / Payment Link
    case PaymentLink  = 'payment_link';
    case Invoice      = 'invoice';

    public function isVirtualAccount(): bool
    {
        return str_starts_with($this->value, 'va_');
    }

    public function isEWallet(): bool
    {
        return in_array($this, [
            self::Gopay, self::Shopeepay, self::Ovo,
            self::Dana, self::Linkaja, self::Sakuku,
        ], true);
    }
}
```

### 3.3 Capability.php

```php
<?php

namespace Aliziodev\PayId\Enums;

enum Capability: string
{
    case Charge                = 'charge';
    case Status                = 'status';
    case Refund                = 'refund';
    case Cancel                = 'cancel';
    case Expire                = 'expire';
    case WebhookVerification   = 'webhook_verification';
    case WebhookParsing        = 'webhook_parsing';
}
```

---

## 4. DTO Definitions

### 4.1 ChargeRequest.php

```php
<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentChannel;
use Carbon\Carbon;

final class ChargeRequest
{
    public function __construct(
        public readonly string $merchantOrderId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly PaymentChannel $channel,
        public readonly CustomerData $customer,
        public readonly array $items = [],
        public readonly ?string $description = null,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $successUrl = null,
        public readonly ?string $failureUrl = null,
        public readonly ?Carbon $expiresAt = null,
        public readonly array $metadata = [],
    ) {}

    public static function make(array $data): self
    {
        return new self(
            merchantOrderId: $data['merchant_order_id'],
            amount: $data['amount'],
            currency: $data['currency'] ?? 'IDR',
            channel: $data['channel'] instanceof PaymentChannel
                ? $data['channel']
                : PaymentChannel::from($data['channel']),
            customer: $data['customer'] instanceof CustomerData
                ? $data['customer']
                : CustomerData::make($data['customer']),
            items: array_map(
                fn($item) => $item instanceof ItemData ? $item : ItemData::make($item),
                $data['items'] ?? []
            ),
            description: $data['description'] ?? null,
            callbackUrl: $data['callback_url'] ?? null,
            successUrl: $data['success_url'] ?? null,
            failureUrl: $data['failure_url'] ?? null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
```

### 4.2 ChargeResponse.php

```php
<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentStatus;
use Carbon\Carbon;

final class ChargeResponse
{
    public function __construct(
        public readonly string $providerName,
        public readonly string $providerTransactionId,
        public readonly string $merchantOrderId,
        public readonly PaymentStatus $status,
        public readonly array $rawResponse,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $qrString = null,
        public readonly ?string $vaNumber = null,
        public readonly ?string $vaBankCode = null,
        public readonly ?Carbon $expiresAt = null,
    ) {}
}
```

### 4.3 NormalizedWebhook.php

```php
<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Carbon\Carbon;

final class NormalizedWebhook
{
    public function __construct(
        public readonly string $provider,
        public readonly string $merchantOrderId,
        public readonly PaymentStatus $status,
        public readonly bool $signatureValid,
        public readonly array $rawPayload,
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $eventType = null,
        public readonly ?int $amount = null,
        public readonly ?string $currency = null,
        public readonly ?PaymentChannel $channel = null,
        public readonly ?Carbon $occurredAt = null,
    ) {}
}
```

---

## 5. PayIdManager Blueprint

```php
<?php

namespace Aliziodev\PayId\Managers;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Contracts\SupportsCharge;
use Aliziodev\PayId\Contracts\SupportsStatus;
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Exceptions\DriverNotFoundException;
use Aliziodev\PayId\Exceptions\UnsupportedCapabilityException;
use Aliziodev\PayId\Factories\DriverFactory;
use Illuminate\Contracts\Config\Repository as Config;

class PayIdManager
{
    protected array $resolvedDrivers = [];
    protected ?string $activeDriver = null;

    public function __construct(
        protected Config $config,
        protected DriverFactory $factory,
    ) {}

    /**
     * Set driver yang aktif untuk request ini.
     */
    public function driver(string $name): static
    {
        $clone = clone $this;
        $clone->activeDriver = $name;
        return $clone;
    }

    /**
     * Buat transaksi payment.
     */
    public function charge(ChargeRequest $request): ChargeResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Charge);

        /** @var SupportsCharge $driver */
        return $driver->charge($request);
    }

    /**
     * Cek status transaksi.
     */
    public function status(string $merchantOrderId): StatusResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Status);

        /** @var SupportsStatus $driver */
        return $driver->status($merchantOrderId);
    }

    /**
     * Resolve driver berdasarkan activeDriver atau default config.
     */
    protected function resolveDriver(): DriverInterface
    {
        $name = $this->activeDriver ?? $this->config->get('payid.default');

        if (!isset($this->resolvedDrivers[$name])) {
            $config = $this->config->get("payid.drivers.{$name}");

            if (!$config) {
                throw new DriverNotFoundException($name);
            }

            $this->resolvedDrivers[$name] = $this->factory->make($name, $config);
        }

        return $this->resolvedDrivers[$name];
    }

    /**
     * Assert bahwa driver mendukung capability yang dibutuhkan.
     */
    protected function assertSupports(DriverInterface $driver, Capability $capability): void
    {
        if (!$driver->supports($capability)) {
            throw new UnsupportedCapabilityException($driver->getName(), $capability);
        }
    }
}
```

---

## 6. WebhookProcessor Blueprint

```php
<?php

namespace Aliziodev\PayId\Webhooks;

use Aliziodev\PayId\Contracts\SupportsWebhookParsing;
use Aliziodev\PayId\Contracts\SupportsWebhookVerification;
use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\Events\WebhookParsingFailed;
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Events\WebhookVerificationFailed;
use Aliziodev\PayId\Managers\PayIdManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;

class WebhookProcessor
{
    public function __construct(
        protected PayIdManager $manager,
        protected LoggerInterface $logger,
    ) {}

    public function handle(Request $request, string $driverName): Response
    {
        $driver = $this->manager->driver($driverName)->resolveDriver();

        // Step 1: Verifikasi signature
        if ($driver instanceof SupportsWebhookVerification) {
            $verified = $driver->verifyWebhook($request);

            $this->logger->info('payid.webhook.verification', [
                'driver' => $driverName,
                'result' => $verified,
            ]);

            if (!$verified) {
                event(new WebhookVerificationFailed($driverName, $request));
                return response('Unauthorized', 401);
            }
        }

        // Step 2: Parse dan normalisasi
        if (!$driver instanceof SupportsWebhookParsing) {
            $this->logger->warning('payid.webhook.no_parser', ['driver' => $driverName]);
            return response('OK', 200);
        }

        try {
            $normalized = $driver->parseWebhook($request);
        } catch (\Throwable $e) {
            $this->logger->error('payid.webhook.parsing_failed', [
                'driver' => $driverName,
                'error'  => $e->getMessage(),
            ]);
            event(new WebhookParsingFailed($driverName, $request, $e));
            return response('Unprocessable', 422);
        }

        // Step 3: Dispatch event
        $this->logger->info('payid.webhook.received', [
            'driver'    => $driverName,
            'order_id'  => $normalized->merchantOrderId,
            'status'    => $normalized->status->value,
        ]);

        event(new WebhookReceived($normalized));

        return response('OK', 200);
    }
}
```

---

## 7. Driver Package Blueprint (Midtrans)

Contoh blueprint untuk driver Midtrans sebagai referensi pembuatan driver:

```
payid-midtrans/
├── src/
│   ├── MidtransDriver.php
│   ├── MidtransConfig.php
│   ├── Mappers/
│   │   ├── ChargeRequestMapper.php
│   │   ├── ChargeResponseMapper.php
│   │   ├── StatusResponseMapper.php
│   │   └── WebhookMapper.php
│   ├── Webhooks/
│   │   ├── MidtransSignatureVerifier.php
│   │   └── MidtransWebhookParser.php
│   ├── Exceptions/
│   │   └── MidtransApiException.php
│   └── MidtransServiceProvider.php
├── config/
│   └── midtrans.php
├── tests/
│   ├── Unit/
│   │   ├── Mappers/
│   │   └── Webhooks/
│   └── Fixtures/
│       ├── webhook-paid.json
│       ├── webhook-pending.json
│       ├── webhook-expire.json
│       └── charge-response.json
└── composer.json
```

### 7.1 MidtransDriver Skeleton

```php
<?php

namespace Aliziodev\PayIdMidtrans;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Contracts\HasCapabilities;
use Aliziodev\PayId\Contracts\SupportsCharge;
use Aliziodev\PayId\Contracts\SupportsStatus;
use Aliziodev\PayId\Contracts\SupportsWebhookParsing;
use Aliziodev\PayId\Contracts\SupportsWebhookVerification;
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\Enums\Capability;
use Illuminate\Http\Request;

class MidtransDriver implements
    DriverInterface,
    SupportsCharge,
    SupportsStatus,
    SupportsWebhookVerification,
    SupportsWebhookParsing
{
    use HasCapabilities;

    public function __construct(
        protected MidtransConfig $config,
        protected ChargeRequestMapper $chargeMapper,
        protected ChargeResponseMapper $responseMapper,
        protected StatusResponseMapper $statusMapper,
        protected MidtransWebhookParser $webhookParser,
        protected MidtransSignatureVerifier $signatureVerifier,
    ) {}

    public function getName(): string
    {
        return 'midtrans';
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
        $payload = $this->chargeMapper->toProvider($request);
        // ... HTTP call ke Midtrans API
        // ... Handle response atau exception
        return $this->responseMapper->fromProvider($providerResponse);
    }

    public function status(string $merchantOrderId): StatusResponse
    {
        // ... HTTP call ke Midtrans status API
        return $this->statusMapper->fromProvider($providerResponse);
    }

    public function verifyWebhook(Request $request): bool
    {
        return $this->signatureVerifier->verify($request);
    }

    public function parseWebhook(Request $request): NormalizedWebhook
    {
        return $this->webhookParser->parse($request);
    }
}
```

---

## 8. PayIdFake Blueprint

```php
<?php

namespace Aliziodev\PayId\Testing;

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\StatusResponse;
use PHPUnit\Framework\Assert;

class PayIdFake
{
    protected array $chargeResponses = [];
    protected array $statusResponses = [];
    protected array $recordedCharges = [];
    protected array $recordedStatuses = [];
    protected ?string $usedDriver = null;

    public function fakeCharge(ChargeResponse $response): void
    {
        $this->chargeResponses[] = $response;
    }

    public function fakeStatus(StatusResponse $response): void
    {
        $this->statusResponses[] = $response;
    }

    public function recordCharge(ChargeRequest $request): void
    {
        $this->recordedCharges[] = $request;
    }

    public function recordDriver(string $driver): void
    {
        $this->usedDriver = $driver;
    }

    // Assertions

    public function assertCharged(int $times = 1): void
    {
        Assert::assertCount($times, $this->recordedCharges,
            "Expected {$times} charge(s), got " . count($this->recordedCharges)
        );
    }

    public function assertNothingCharged(): void
    {
        Assert::assertEmpty($this->recordedCharges, 'No charges were expected but some were recorded.');
    }

    public function assertDriverUsed(string $driver): void
    {
        Assert::assertEquals($driver, $this->usedDriver,
            "Expected driver [{$driver}] but [{$this->usedDriver}] was used."
        );
    }

    public function assertChargedWith(callable $callback): void
    {
        $found = false;
        foreach ($this->recordedCharges as $charge) {
            if ($callback($charge) === true) {
                $found = true;
                break;
            }
        }
        Assert::assertTrue($found, 'No charge matching the given criteria was found.');
    }
}
```

---

## 9. Konfigurasi Package Detail

```php
<?php

// config/payid.php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    | Driver yang digunakan secara default jika tidak ditentukan secara eksplisit.
    | Nilai ini merujuk ke key di array 'drivers' di bawah.
    */
    'default' => env('PAYID_DEFAULT_DRIVER', 'midtrans'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    | Mata uang default yang digunakan jika tidak ditentukan di ChargeRequest.
    */
    'currency' => env('PAYID_DEFAULT_CURRENCY', 'IDR'),

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers
    |--------------------------------------------------------------------------
    | Daftar driver yang dikonfigurasi. Key adalah nama driver, value adalah
    | array konfigurasi yang akan dioper ke driver saat di-resolve.
    |
    | Key 'driver' wajib ada dan harus sesuai dengan nama driver yang terdaftar.
    */
    'drivers' => [

        'midtrans' => [
            'driver'      => 'midtrans',
            'environment' => env('MIDTRANS_ENV', 'sandbox'), // sandbox | production
            'server_key'  => env('MIDTRANS_SERVER_KEY'),
            'client_key'  => env('MIDTRANS_CLIENT_KEY'),
            'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        ],

        'xendit' => [
            'driver'         => 'xendit',
            'environment'    => env('XENDIT_ENV', 'test'), // test | live
            'secret_key'     => env('XENDIT_SECRET_KEY'),
            'public_key'     => env('XENDIT_PUBLIC_KEY'),
            'webhook_token'  => env('XENDIT_WEBHOOK_TOKEN'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Settings
    |--------------------------------------------------------------------------
    | Konfigurasi HTTP client yang digunakan driver untuk berkomunikasi
    | dengan API provider.
    */
    'http' => [
        'timeout'        => env('PAYID_HTTP_TIMEOUT', 30),
        'retry_times'    => env('PAYID_HTTP_RETRY', 1),
        'retry_delay_ms' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'route_prefix'    => env('PAYID_WEBHOOK_PREFIX', 'payid'),
        'route_middleware' => [],
        'queue'           => env('PAYID_WEBHOOK_QUEUE', false),
        'queue_name'      => env('PAYID_WEBHOOK_QUEUE_NAME', 'default'),
        'queue_connection'=> env('PAYID_WEBHOOK_QUEUE_CONNECTION', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled'        => env('PAYID_LOGGING', true),
        'channel'        => env('PAYID_LOG_CHANNEL', null), // null = default Laravel channel
        'mask_sensitive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Credential Resolver (untuk multi-tenant)
    |--------------------------------------------------------------------------
    | Set dengan callable jika credential perlu di-resolve secara dinamis.
    | Contoh: null (gunakan konfigurasi statis di atas)
    |
    | Signature: function(string $driver, Request $request): array
    */
    'credential_resolver' => null,

];
```

---

## 10. Route Webhook

```php
<?php

// routes/webhooks.php

use Aliziodev\PayId\Laravel\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post(
    config('payid.webhook.route_prefix', 'payid') . '/webhook/{driver}',
    WebhookController::class
)
->middleware(config('payid.webhook.route_middleware', []))
->name('payid.webhook');
```

---

## 11. composer.json Core Package

```json
{
    "name": "aliziodev/payid",
    "description": "Unified Laravel Payment Orchestrator for Indonesian Payment Gateways",
    "type": "library",
    "license": "MIT",
    "keywords": ["laravel", "payment", "gateway", "indonesia", "midtrans", "xendit", "doku"],
    "authors": [
        {
            "name": "Alizio Dev",
            "email": "hello@aliziodev.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "illuminate/contracts": "^11.0|^12.0",
        "illuminate/support": "^11.0|^12.0",
        "illuminate/http": "^11.0|^12.0",
        "illuminate/routing": "^11.0|^12.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "phpunit/phpunit": "^11.0",
        "pestphp/pest": "^3.0",
        "laravel/pint": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Aliziodev\\PayId\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Aliziodev\\PayId\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Aliziodev\\PayId\\Laravel\\PayIdServiceProvider"
            ],
            "aliases": {
                "PayId": "Aliziodev\\PayId\\Laravel\\Facades\\PayId"
            }
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## 12. Driver Authoring Checklist

Checklist yang wajib dipenuhi saat membuat driver baru:

### Wajib
- [ ] Mengimplementasikan `DriverInterface`
- [ ] Menggunakan trait `HasCapabilities`
- [ ] `getName()` mengembalikan string identifier unik
- [ ] `getCapabilities()` mendeklarasikan semua capability yang benar-benar didukung
- [ ] Semua exception driver di-wrap ke `PayIdException` atau subclass-nya
- [ ] `charge()` mengembalikan `ChargeResponse` yang valid
- [ ] `status()` mengembalikan `StatusResponse` yang valid
- [ ] `verifyWebhook()` menggunakan raw request body (bukan JSON parsed)
- [ ] `parseWebhook()` mengembalikan `NormalizedWebhook` yang valid
- [ ] Semua field sensitif di-mask sebelum logging
- [ ] Raw response provider selalu disertakan di response DTO

### Testing (Wajib)
- [ ] Unit test untuk semua mapper
- [ ] Unit test untuk webhook verifier
- [ ] Unit test untuk webhook parser
- [ ] Fixture JSON untuk minimal 3 skenario webhook (paid, pending, expired)
- [ ] Contract test menggunakan test suite core

### Dokumentasi (Wajib)
- [ ] README driver menjelaskan cara instalasi
- [ ] Daftar environment variables yang dibutuhkan
- [ ] Daftar capabilities yang didukung
- [ ] Daftar PaymentChannel yang didukung driver ini
- [ ] Instruksi setup webhook di dashboard provider
