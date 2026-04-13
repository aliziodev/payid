# Driver Authoring Guide

Panduan ini menjelaskan langkah praktis membuat driver payment gateway baru untuk PayID.

## 1. Tujuan Driver

Driver bertugas menerjemahkan API provider ke kontrak standar PayID.

Driver tidak boleh:
- mengubah API publik core
- memasukkan logic aplikasi host
- mendispatch event core secara langsung

## 2. Paket dan Struktur Minimal

Disarankan buat package terpisah, contoh: `aliziodev/payid-xendit`.

Struktur minimal:

```text
src/
  YourDriver.php
  YourDriverConfig.php
  YourServiceProvider.php
  Mappers/
  Webhooks/
tests/
composer.json
```

## 3. Implementasi Contract

### Wajib
- `DriverInterface`

### Capability-based (pilih sesuai dukungan provider)
- `SupportsCharge`
- `SupportsDirectCharge`
- `SupportsStatus`
- `SupportsRefund`
- `SupportsCancel`
- `SupportsExpire`
- `SupportsApprove`
- `SupportsDeny`
- `SupportsSubscription`
- `SupportsWebhookVerification`
- `SupportsWebhookParsing`

Gunakan `HasCapabilities` trait untuk implementasi `supports()`.

## 4. Registrasi Driver ke Core

Di service provider driver, daftarkan resolver:

```php
$factory->extend('your-driver', function (array $config): YourDriver {
    return new YourDriver(/* dependencies */);
});
```

Pastikan key `driver` di config host sesuai resolver name.

## 5. Mapping yang Disarankan

Pisahkan mapper untuk menjaga driver tetap clean:
- Request mapper: DTO PayID -> payload provider
- Response mapper: payload provider -> DTO PayID
- Webhook mapper: payload webhook provider -> `NormalizedWebhook`

## 6. Error Handling

Gunakan exception standar PayID:
- `ProviderApiException`
- `ProviderNetworkException`
- `PayloadMappingException`

Hindari melempar exception mentah dari HTTP client/provider.

## 7. Webhook Contract

Jika provider mendukung webhook:
- implementasikan `SupportsWebhookVerification`
- implementasikan `SupportsWebhookParsing`
- verifikasi gunakan raw request body/signature sesuai spec provider

## 8. Testing Minimum Driver

Wajib ada test untuk:
- `charge()` happy-path
- `status()` mapping
- webhook verification valid/invalid
- webhook parsing ke `NormalizedWebhook`
- capability declaration konsisten

Tambahkan fixture payload nyata dari sandbox provider.

## 9. Integrasi Dengan Aplikasi Host

Dokumentasikan minimal:
- env keys yang diperlukan
- contoh config
- URL webhook yang harus didaftarkan di dashboard provider
- contoh penggunaan charge/status

## 10. Release Checklist

Sebelum publish driver:
- seluruh test driver lulus
- static analysis lulus
- README driver lengkap
- versi `aliziodev/payid` pada composer `require` sesuai kompatibilitas
