# PayID

> Unified Laravel Payment Orchestrator for Indonesian Payment Gateways

PayID adalah package Laravel yang menyatukan berbagai payment gateway Indonesia dalam satu API yang konsisten dan extensible. Integrasikan sekali, gunakan provider mana saja melalui model driver.

---

## Requirements

- PHP 8.2+
- Laravel 11.x, 12.x, atau 13.x

---

## Instalasi

Install PayID core:

```bash
composer require aliziodev/payid
```

Install driver provider yang diinginkan:

```bash
composer require aliziodev/payid-midtrans
composer require aliziodev/payid-xendit
```

Publish konfigurasi:

```bash
php artisan vendor:publish --tag=payid-config
```

Opsional, gunakan installer interaktif:

```bash
php artisan payid:install
```

Installer akan memandu:

- pemilihan driver gateway
- pemilihan transaction stack: `payid-transactions` atau `manual`

---

## Konfigurasi

Tambahkan ke `.env`:

```env
PAYID_DEFAULT_DRIVER=midtrans

MIDTRANS_ENV=sandbox
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
```

---

## Penggunaan Dasar

### Membuat Transaksi

```php
use Aliziodev\PayId\DTO\ChargeRequest;
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

return redirect($response->paymentUrl);
```

### Cek Status Transaksi

```php
$status = PayId::status('ORDER-001');

if ($status->status->isSuccessful()) {
    // Tandai order sebagai dibayar
}
```

### Ganti Driver Saat Runtime

```php
$response = PayId::driver('xendit')->charge($request);
```

### Operasi Lain yang Tersedia

PayID manager/facade juga menyediakan:

- `directCharge(ChargeRequest $request)`
- `refund(RefundRequest $request)`
- `cancel(string $merchantOrderId)`
- `expire(string $merchantOrderId)`
- `approve(string $merchantOrderId)`
- `deny(string $merchantOrderId)`
- `createSubscription(SubscriptionRequest $request)`
- `getSubscription(string $providerSubscriptionId)`
- `updateSubscription(UpdateSubscriptionRequest $request)`
- `pauseSubscription(string $providerSubscriptionId)`
- `resumeSubscription(string $providerSubscriptionId)`
- `cancelSubscription(string $providerSubscriptionId)`
- `supports(Capability $capability)`

### Menangani Webhook

Daftarkan URL webhook ke dashboard provider:
`https://yourdomain.com/payid/webhook/midtrans`

Tangkap event di listener:

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

---

## Testing

```php
$fake = PayId::fake();

$fake->fakeCharge(ChargeResponse::make([
    'provider_name'           => 'midtrans',
    'provider_transaction_id' => 'TRX-001',
    'merchant_order_id'       => 'ORDER-001',
    'status'                  => PaymentStatus::Pending,
    'payment_url'             => 'https://example.com/pay',
    'raw_response'            => [],
]));

// Jalankan kode yang memanggil PayId::charge(...)

$fake->assertCharged();
```

Fake helper lain yang tersedia termasuk:

- `fakeDirectCharge`, `fakeStatus`, `fakeRefund`, `fakeCancel`, `fakeExpire`
- `fakeApprove`, `fakeDeny`, `fakeSubscription`
- assertion seperti `assertDirectCharged`, `assertStatusChecked`, `assertRefunded`, `assertNothingRecorded`

---

## Driver yang Tersedia

| Driver | Package | Status |
|--------|---------|--------|
| Midtrans | `aliziodev/payid-midtrans` | Stable |
| Xendit   | `aliziodev/payid-xendit`   | Stable |
| iPaymu   | `aliziodev/payid-ipaymu`   | Beta |
| Nicepay  | `aliziodev/payid-nicepay`  | Stable |
| DOKU     | `aliziodev/payid-doku`     | Coming Soon |

---

## Dokumentasi

- [R&D Document](docs/01-rnd-document.md)
- [Technical Architecture](docs/02-technical-architecture.md)
- [Package Blueprint](docs/03-package-blueprint.md)
- [Implementation Roadmap](docs/04-implementation-roadmap.md)
- [README and Docs Structure Draft](docs/05-readme-and-docs-structure.md)
- [Production Readiness Checklist](docs/06-production-readiness.md)
- [Driver Authoring Guide](docs/07-driver-authoring-guide.md)
- [Driver Acceptance Checklist](docs/08-driver-acceptance-checklist.md)
- [Midtrans Complete Usage Guide](docs/09-midtrans-complete-usage.md)
- [Xendit Complete Usage Guide](docs/10-xendit-complete-usage.md)
- [Driver Feature Matrix (Midtrans vs Xendit vs iPaymu vs Nicepay)](docs/11-driver-feature-matrix.md)
- [Xendit Extension API Quick Reference](docs/12-xendit-extension-api-quick-reference.md)
- [Midtrans Extension API Quick Reference](docs/13-midtrans-extension-api-quick-reference.md)
- [iPaymu Complete Usage Guide](docs/14-ipaymu-complete-usage.md)
- [iPaymu Extension API Quick Reference](docs/15-ipaymu-extension-api-quick-reference.md)
- [Nicepay Complete Usage Guide](docs/16-nicepay-complete-usage.md)
- [Nicepay Extension API Quick Reference](docs/17-nicepay-extension-api-quick-reference.md)
- [Diagram Index](docs/diagram/README.md)
- [PayID Complete System Flow Diagram](docs/diagram/01-payid-complete-system-flow.md)
- [Checkout and Payment Lifecycle Flow](docs/diagram/02-checkout-and-lifecycle-flow.md)
- [Webhook Processing Flow](docs/diagram/03-webhook-processing-flow.md)
- [Subscription Flow](docs/diagram/04-subscription-flow.md)
- [Driver Extension Flow](docs/diagram/05-driver-extension-flow.md)
- [ADR: Core and Driver Separation](docs/internals/adr/001-core-driver-separation.md)
- [ADR: Capability-based Contracts](docs/internals/adr/002-capability-based-contracts.md)
- [ADR: Immutable DTO](docs/internals/adr/003-immutable-dto.md)
- [ADR: Webhook Pipeline](docs/internals/adr/004-webhook-pipeline.md)

---

## Status Kesiapan

- Test suite: passing
- Static analysis (PHPStan): passing
- Coverage report: butuh coverage driver (Xdebug/PCOV/phpdbg-compatible) di environment

## Known Limitations

- Coverage report belum dapat dijalankan jika environment belum memuat driver coverage.
- Driver iPaymu sudah tersedia pada level beta dan dapat dipakai untuk flow charge/status/webhook.
- Driver Nicepay sudah final dan berstatus stable, mencakup fitur SNAP + V2 melalui extension method.
- Fitur queue processing webhook disiapkan pada konfigurasi, tetapi implementasi orchestration async tetap perlu ditangani di aplikasi host.

---

## Kontribusi

Lihat [CONTRIBUTING.md](CONTRIBUTING.md) untuk panduan berkontribusi.

## Security

Untuk pelaporan vulnerability, lihat [SECURITY.md](SECURITY.md).

## Upgrade

Panduan upgrade antar versi tersedia di [UPGRADE.md](UPGRADE.md).

## Changelog

Riwayat perubahan tersedia di [CHANGELOG.md](CHANGELOG.md).

---

## Lisensi

MIT License.
