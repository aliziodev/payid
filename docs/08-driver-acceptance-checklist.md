# Driver Acceptance Checklist

Checklist ini dipakai sebelum driver baru dinyatakan siap digunakan.

## A. Contract & Capability

- Driver mengimplementasikan `DriverInterface`.
- `getName()` stabil dan unik.
- `getCapabilities()` hanya berisi enum `Capability` yang benar-benar didukung.
- Semua capability yang dideklarasikan benar-benar terimplementasi.

## B. Mapping & Domain Consistency

- Request mapper memetakan DTO PayID ke payload provider dengan benar.
- Response mapper menghasilkan DTO PayID yang valid.
- Status provider dipetakan konsisten ke `PaymentStatus`.
- Channel provider dipetakan konsisten ke `PaymentChannel` (jika relevan).

## C. Error Handling

- Error HTTP provider dipetakan ke `ProviderApiException`.
- Error jaringan dipetakan ke `ProviderNetworkException`.
- Payload tidak valid dipetakan ke `PayloadMappingException` (bila relevan).
- Data sensitif tidak bocor di exception context/log.

## D. Webhook

- Signature verification tervalidasi terhadap spec provider.
- Parsing webhook menghasilkan `NormalizedWebhook` yang konsisten.
- Kasus invalid signature menghasilkan flow unauthorized.
- Kasus parsing gagal menghasilkan flow unprocessable.

## E. Config & Credential

- Semua env key terdokumentasi jelas.
- Driver mendukung config timeout/retry jika memakai HTTP client.
- Integrasi credential resolver (`resolveCredentialsUsing`) tetap kompatibel.

## F. Testing

- Unit test driver lulus.
- Integration test webhook lulus.
- Contract test core lulus untuk driver ini (atau ekuivalennya di package driver).
- Static analysis lulus (`phpstan`/`larastan`).

## G. Documentation

- README driver menjelaskan install, config, usage, webhook setup.
- Contoh payload/fixtures tersedia untuk skenario utama.
- Known limitations provider didokumentasikan.

## H. Release Gate

- Versi package mengikuti semantic versioning.
- Changelog diperbarui.
- Kompatibilitas versi `aliziodev/payid` dijelaskan di composer constraint.
