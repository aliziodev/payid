# PayID — Implementation Roadmap
> Rencana Implementasi Bertahap
> Version: 1.0.0-draft | Date: 2026-04-13

---

## Gambaran Umum Roadmap

```
Phase 1 — Foundation          [Core Package + Scaffold]
Phase 2 — First Drivers       [Midtrans + Xendit]
Phase 3 — Hardening           [Testing, Observability, Stability]
Phase 4 — Expansion           [DOKU, iPaymu, Refund/Cancel/Expire]
Phase 5 — Orchestration Adv.  [Smart Routing, Multi-tenant, Fallback]
```

Setiap phase dirancang agar dapat di-release secara independen. Phase berikutnya tidak mengubah public API yang sudah ditetapkan di phase sebelumnya, kecuali dengan notice deprecation yang jelas.

---

## Phase 1 — Foundation

**Tujuan:** Membangun seluruh fondasi core package yang clean, testable, dan siap digunakan sebagai basis driver.

**Output:** Package `aliziodev/payid` yang siap di-publish ke Packagist (versi `0.1.0-alpha`), meskipun belum ada driver nyata.

### 1.1 Scaffold Package

**Tugas:**
- [ ] Buat struktur folder sesuai blueprint (`src/`, `config/`, `routes/`, `tests/`, `docs/`)
- [ ] Setup `composer.json` dengan dependency minimal
- [ ] Setup `phpunit.xml` dan Pest
- [ ] Setup Laravel Pint untuk code style
- [ ] Setup `.gitignore`, `README.md` awal, `CHANGELOG.md`
- [ ] Setup GitHub Actions (opsional di phase ini)

**Output:**
```
payid/
├── src/
├── config/payid.php
├── routes/webhooks.php
├── tests/
├── composer.json
├── phpunit.xml
└── README.md (skeleton)
```

### 1.2 Contracts (Interfaces)

**Tugas:**
- [ ] `DriverInterface.php`
- [ ] `SupportsCharge.php`
- [ ] `SupportsStatus.php`
- [ ] `SupportsRefund.php`
- [ ] `SupportsCancel.php`
- [ ] `SupportsExpire.php`
- [ ] `SupportsWebhookVerification.php`
- [ ] `SupportsWebhookParsing.php`
- [ ] `HasCapabilities.php` (trait)

**Prinsip:** Semua interface harus kecil, fokus, dan tidak mengandung default implementation.

### 1.3 Enums

**Tugas:**
- [ ] `PaymentStatus.php` — semua status standar + helper methods
- [ ] `PaymentChannel.php` — semua channel standar + helper methods
- [ ] `Capability.php` — enum capability untuk driver declaration

**Prinsip:** Enum adalah kontrak domain — setiap perubahan ke enum dianggap potential breaking change.

### 1.4 DTO

**Tugas:**
- [ ] `ChargeRequest.php` — dengan static factory `make()`
- [ ] `ChargeResponse.php`
- [ ] `StatusResponse.php`
- [ ] `RefundRequest.php`
- [ ] `RefundResponse.php`
- [ ] `CustomerData.php`
- [ ] `ItemData.php`
- [ ] `NormalizedWebhook.php`

**Prinsip:** Semua DTO `readonly` (immutable). Gunakan PHP 8.2 `readonly` properties.

### 1.5 Exceptions

**Tugas:**
- [ ] `PayIdException.php` (base)
- [ ] `ConfigurationException.php`
- [ ] `MissingDriverConfigException.php`
- [ ] `InvalidCredentialException.php`
- [ ] `DriverNotFoundException.php`
- [ ] `DriverResolutionException.php`
- [ ] `UnsupportedCapabilityException.php`
- [ ] `ProviderApiException.php`
- [ ] `ProviderResponseException.php`
- [ ] `ProviderNetworkException.php`
- [ ] `WebhookVerificationException.php`
- [ ] `WebhookParsingException.php`
- [ ] `PayloadMappingException.php`

### 1.6 Events

**Tugas:**
- [ ] `PaymentCharged.php`
- [ ] `PaymentStatusChecked.php`
- [ ] `WebhookReceived.php`
- [ ] `WebhookVerificationFailed.php`
- [ ] `WebhookParsingFailed.php`

### 1.7 Core Manager & Factory

**Tugas:**
- [ ] `DriverFactory.php` — factory yang resolve driver dari config
- [ ] `PayIdManager.php` — orchestrator utama
  - driver resolution (default + runtime switching)
  - capability checking sebelum delegasi ke driver
  - logging hooks

### 1.8 Webhook Pipeline

**Tugas:**
- [ ] `WebhookProcessor.php` — full pipeline (verifikasi → parsing → normalisasi → event)
- [ ] `WebhookResult.php` — result object
- [ ] `WebhookController.php` — thin controller yang delegate ke processor

### 1.9 Support Utilities

**Tugas:**
- [ ] `Money.php` — konversi nominal (IDR ke cents, format rupiah)
- [ ] `Mask.php` — masking data sensitif untuk logging
- [ ] `Signature.php` — common signature utilities (HMAC, hash)
- [ ] `PayIdHttpClient.php` — wrapper HTTP client dengan timeout, retry, logging

### 1.10 Laravel Integration

**Tugas:**
- [ ] `PayIdServiceProvider.php`
  - register binding di container
  - publish config
  - load routes
- [ ] `PayId.php` (Facade)
- [ ] Auto-discovery di `composer.json`

### 1.11 Testing Foundation

**Tugas:**
- [ ] `PayIdFake.php` — fake manager untuk testing
- [ ] `FakeDriver.php` — fake driver implementation
- [ ] `PayIdAssertions.php` — assertion helpers trait
- [ ] Unit test untuk semua DTO, enum, exception, manager

**Milestone Phase 1:** Core package bisa di-install di Laravel, service provider boot dengan benar, config publish berjalan, fake testing utility tersedia. Belum ada driver nyata.

---

## Phase 2 — First Drivers

**Tujuan:** Membuat dua driver pertama sebagai validasi desain core dan referensi implementasi driver.

**Output:** Package `aliziodev/payid-midtrans` dan `aliziodev/payid-xendit` (versi `0.1.0`)

**Mengapa Midtrans dan Xendit:**
- Keduanya adalah provider yang paling umum digunakan di Indonesia
- Memiliki model payment yang cukup berbeda → memvalidasi fleksibilitas core
- Midtrans: model server-side charge dengan berbagai channel
- Xendit: model invoice-based yang lebih sederhana
- Keduanya memiliki dokumentasi API yang cukup baik

### 2.1 Midtrans Driver (`aliziodev/payid-midtrans`)

**Tugas:**
- [ ] Scaffold `payid-midtrans` package
- [ ] `MidtransConfig.php` — value object konfigurasi
- [ ] `MidtransDriver.php` — implementasi utama
- [ ] `ChargeRequestMapper.php` — map ChargeRequest ke Midtrans Core API payload
- [ ] `ChargeResponseMapper.php` — map Midtrans response ke ChargeResponse
- [ ] `StatusResponseMapper.php` — map Midtrans status response ke StatusResponse
- [ ] `MidtransSignatureVerifier.php` — SHA512 signature verification
- [ ] `MidtransWebhookParser.php` — parse notification ke NormalizedWebhook
- [ ] `WebhookMapper.php` — map Midtrans notification fields ke PayID standar
- [ ] `MidtransServiceProvider.php`

**Channels yang Ditargetkan (MVP):**
- QRIS (via Snap API atau Core API)
- GoPay (via Snap API)
- Virtual Account BCA, BNI, BRI, Mandiri
- Credit Card (basis)

**Fixtures untuk Testing:**
- [ ] `webhook-paid.json` — contoh notifikasi sukses
- [ ] `webhook-pending.json`
- [ ] `webhook-expire.json`
- [ ] `webhook-cancel.json`
- [ ] `charge-snap-response.json`
- [ ] `status-response.json`

**Capabilities yang Diimplementasikan:**
- `Capability::Charge`
- `Capability::Status`
- `Capability::WebhookVerification`
- `Capability::WebhookParsing`

### 2.2 Xendit Driver (`aliziodev/payid-xendit`)

**Tugas:**
- [ ] Scaffold `payid-xendit` package
- [ ] `XenditConfig.php`
- [ ] `XenditDriver.php`
- [ ] `InvoiceRequestMapper.php`
- [ ] `InvoiceResponseMapper.php`
- [ ] `XenditStatusResponseMapper.php`
- [ ] `XenditWebhookVerifier.php` — webhook-token header verification
- [ ] `XenditWebhookParser.php`
- [ ] `XenditServiceProvider.php`

**Channels yang Ditargetkan (MVP):**
- Invoice (payment link)
- Virtual Account BCA, BNI, BRI, Mandiri
- QRIS
- GoPay, OVO, Dana, ShopeePay (via e-wallet API)

**Capabilities yang Diimplementasikan:**
- `Capability::Charge`
- `Capability::Status`
- `Capability::WebhookVerification`
- `Capability::WebhookParsing`

### 2.3 Contract Tests

Setelah dua driver siap, buat contract test suite di core yang dijalankan untuk semua driver:

```
tests/
└── Contracts/
    ├── DriverContractTest.php        ← Test bahwa driver implement interface dengan benar
    ├── ChargeContractTest.php        ← Test bahwa charge menghasilkan ChargeResponse valid
    ├── StatusContractTest.php        ← Test bahwa status menghasilkan StatusResponse valid
    └── WebhookContractTest.php       ← Test bahwa webhook handling berjalan sesuai pipeline
```

**Milestone Phase 2:** Developer dapat menggunakan Midtrans dan Xendit melalui PayID dengan API yang seragam. Webhook berfungsi. Contract test lulus untuk kedua driver.

---

## Phase 3 — Hardening

**Tujuan:** Meningkatkan kualitas, observability, dan stabilitas sebelum mencapai `v1.0.0`.

**Output:** Core dan kedua driver naik ke versi `1.0.0`

### 3.1 Logging Enhancement

**Tugas:**
- [ ] Structured logging di semua titik kritis (charge, status, webhook pipeline)
- [ ] Log level yang tepat (DEBUG untuk trace, INFO untuk flow, ERROR untuk failure)
- [ ] Masking otomatis untuk field sensitif di log
- [ ] Configurable log channel (`payid.logging.channel`)
- [ ] Optionally: context propagation (trace ID, order ID di semua log entry terkait)

### 3.2 Exception Enhancement

**Tugas:**
- [ ] Pastikan semua exception membawa context yang cukup (driver name, order ID, original exception)
- [ ] Tambahkan `$context` property ke `PayIdException`
- [ ] Pastikan ProviderApiException menyimpan HTTP status code dan response body (di-mask jika perlu)
- [ ] Review: apakah semua exception sudah dikategorikan dengan benar?

### 3.3 HTTP Client Enhancement

**Tugas:**
- [ ] Retry logic dengan exponential backoff
- [ ] Timeout yang dapat dikonfigurasi per driver
- [ ] Request/response logging (dengan masking)
- [ ] Error mapping dari HTTP error ke exception hierarchy yang tepat

### 3.4 Webhook Fixtures & Snapshot Testing

**Tugas:**
- [ ] Kumpulkan fixture nyata dari Midtrans dan Xendit (dari sandbox)
- [ ] Buat snapshot test untuk memastikan mapping tidak berubah tanpa sadar
- [ ] Test semua kombinasi status + channel yang mungkin

### 3.5 Documentation

**Tugas:**
- [ ] README core yang lengkap
- [ ] README Midtrans driver
- [ ] README Xendit driver
- [ ] Quick start guide
- [ ] Configuration guide
- [ ] Webhook setup guide
- [ ] Testing guide (cara pakai PayIdFake)
- [ ] Custom driver authoring guide
- [ ] Architecture decision records (ADR)
- [ ] CONTRIBUTING.md
- [ ] CHANGELOG.md dengan format standar

### 3.6 CI/CD Setup

**Tugas:**
- [ ] GitHub Actions: run tests di PHP 8.2 dan 8.3
- [ ] GitHub Actions: Laravel 11 dan 12
- [ ] GitHub Actions: code style check (Pint)
- [ ] GitHub Actions: static analysis (Larastan/PHPStan level 6+)

### 3.7 API Stability Review

Sebelum `v1.0.0`, lakukan review menyeluruh:
- [ ] Apakah ada public API yang masih tidak stabil?
- [ ] Apakah nama method sudah konsisten dan intuitif?
- [ ] Apakah DTO fields sudah lengkap dan tepat?
- [ ] Apakah enum values sudah final?
- [ ] Apakah konfigurasi keys sudah final?

**Milestone Phase 3:** Package siap untuk production use. `v1.0.0` di-release. Dokumentasi lengkap tersedia.

---

## Phase 4 — Expansion

**Tujuan:** Memperluas coverage provider dan capability.

**Output:** Driver DOKU dan iPaymu, serta capability Refund, Cancel, Expire.

### 4.1 DOKU Driver (`aliziodev/payid-doku`)

**Channels yang Ditargetkan:**
- Virtual Account BCA, BNI, BRI, Mandiri, CIMB
- QRIS
- Kartu Kredit/Debit
- OVO, Dana (via DOKU)

**Capabilities:**
- Charge
- Status
- Webhook Verification
- Webhook Parsing

### 4.2 iPaymu Driver (`aliziodev/payid-ipaymu`)

**Channels yang Ditargetkan:**
- Virtual Account BCA, BNI, BRI, Mandiri, Permata
- QRIS
- E-wallet (OVO, GoPay, dll via iPaymu)
- COD (opsional)

**Capabilities:**
- Charge
- Status
- Webhook Verification
- Webhook Parsing

### 4.3 Capability: Refund

**Tugas:**
- [ ] `SupportsRefund.php` sudah ada di Phase 1, sekarang implementasikan di driver
- [ ] Implementasikan di Midtrans driver (Midtrans mendukung full dan partial refund)
- [ ] Implementasikan di Xendit driver
- [ ] Update contract test untuk refund
- [ ] Tambahkan `PayId::refund()` di manager
- [ ] Tambahkan `PaymentRefunded` event
- [ ] Tambahkan assertion `assertRefunded()` di fake

### 4.4 Capability: Cancel

**Tugas:**
- [ ] Implementasikan di Midtrans driver
- [ ] Implementasikan di Xendit driver (void/cancel invoice)
- [ ] Update contract test untuk cancel
- [ ] Tambahkan `PayId::cancel()` di manager
- [ ] Tambahkan `PaymentCancelled` event

### 4.5 Capability: Expire

**Tugas:**
- [ ] Implementasikan di Midtrans driver (expire transaction)
- [ ] Update contract test untuk expire

### 4.6 Multi-Merchant / Multi-Credential Support

**Tugas:**
- [ ] Finalisasi API untuk `credential_resolver` di config
- [ ] Dokumentasi penggunaan untuk multi-tenant scenario
- [ ] Test coverage untuk multi-merchant scenario
- [ ] Pastikan driver tidak menyimpan state yang bisa bocor antar tenant

**Milestone Phase 4:** PayID mendukung 4 provider utama Indonesia, dengan capability charge, status, refund, cancel, expire. Multi-merchant scenario terdokumentasi dengan baik.

---

## Phase 5 — Orchestration Advanced

**Tujuan:** Menambahkan kemampuan orkestrasi yang lebih cerdas.

**Output:** Fitur-fitur advanced yang membuat PayID lebih dari sekadar wrapper provider.

### 5.1 Smart Provider Routing

Kemampuan untuk memilih provider terbaik berdasarkan channel yang diminta:

```php
// Contoh API yang diinginkan
PayId::forChannel(PaymentChannel::Qris)->charge($request);
// → Otomatis pilih provider yang mendukung QRIS
```

**Tugas:**
- [ ] `ChannelRouter.php` — logic pemilihan provider berdasarkan channel
- [ ] Config untuk mendefinisikan routing rule
- [ ] Fallback jika provider pertama tidak available

### 5.2 Fallback Provider

Kemampuan retry ke provider lain jika provider utama gagal:

```php
PayId::driver('midtrans')
    ->fallbackTo('xendit')
    ->charge($request);
```

**Tugas:**
- [ ] `FallbackResolver.php`
- [ ] Logging yang jelas saat fallback terjadi
- [ ] Event untuk fallback (`ProviderFallbackOccurred`)
- [ ] Test coverage

### 5.3 Tenant-Aware Config Resolver

Finalisasi dan dokumentasi lengkap untuk multi-tenant:

```php
// Di AppServiceProvider
PayId::resolveCredentialsUsing(function (string $driver, Request $request): array {
    return Tenant::current()->paymentCredentials($driver);
});
```

**Tugas:**
- [ ] Finalisasi resolver API
- [ ] Test coverage untuk tenant credential isolation
- [ ] Dokumentasi lengkap

### 5.4 Metrics Hooks

Kemampuan untuk men-hook ke lifecycle payment untuk keperluan metrics:

```php
PayId::listening(function (PaymentCharged $event) {
    Metrics::increment('payment.charged', ['driver' => $event->driver]);
});
```

**Tugas:**
- [ ] Pastikan semua event sudah lengkap dan konsisten
- [ ] Dokumentasi integrasi dengan sistem observability populer

### 5.5 Capability-Aware Resolution

Kemampuan memilih driver berdasarkan capability yang dibutuhkan:

```php
PayId::withCapability(Capability::Refund)->refund($request);
// → Pilih driver pertama yang mendukung Refund
```

**Milestone Phase 5:** PayID bukan lagi sekadar wrapper — melainkan orchestrator yang cerdas dengan routing, fallback, dan multi-tenant support yang solid.

---

## Milestone Summary

| Phase | Versi Target | Key Deliverables |
|---|---|---|
| Phase 1: Foundation | 0.1.0-alpha | Core package, contracts, DTO, enums, manager, webhook pipeline, fake |
| Phase 2: First Drivers | 0.2.0-beta | Midtrans driver, Xendit driver, contract tests |
| Phase 3: Hardening | 1.0.0 | Full docs, CI, logging, API stability review, production ready |
| Phase 4: Expansion | 1.1.0–1.3.0 | DOKU, iPaymu, Refund/Cancel/Expire, multi-merchant |
| Phase 5: Orchestration | 2.0.0 | Smart routing, fallback, tenant-aware, metrics hooks |

---

## Dependency Graph Antar Phase

```
Phase 1 (Core)
    └── Phase 2 (Drivers)          ← bergantung pada Phase 1
            └── Phase 3 (Hardening)   ← bergantung pada Phase 1 + 2
                    └── Phase 4 (Expansion)  ← bergantung pada Phase 1–3
                            └── Phase 5 (Advanced) ← bergantung pada Phase 1–4
```

Phase 3 (Hardening) wajib selesai sebelum v1.0.0 — tidak boleh skip untuk mengejar fitur Phase 4.

---

## Prinsip Implementasi

1. **Test dulu, implementasi kemudian** — setiap komponen baru dimulai dengan menulis test-nya.
2. **Public API freeze setelah v1.0.0** — setelah rilis stabil, tidak ada breaking change tanpa major version bump.
3. **Satu driver per PR** — jangan campur perubahan core dengan perubahan driver.
4. **Fixtures dari provider asli** — webhook fixtures harus berasal dari sandbox nyata, bukan dibuat-buat.
5. **Dokumentasi adalah syarat release** — tidak ada release tanpa dokumentasi yang up-to-date.
6. **Review contract sebelum Phase berakhir** — setiap akhir phase, review apakah ada contract yang perlu dikuatkan sebelum lanjut ke phase berikutnya.
