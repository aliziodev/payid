# Security Policy

Terima kasih telah membantu menjaga PayID tetap aman.

## Supported Versions

Saat ini fokus patch security diberikan untuk branch versi terbaru yang aktif dikembangkan.

| Version | Supported |
| --- | --- |
| 1.x (latest) | Yes |
| < 1.0 | No |

## Reporting a Vulnerability

Jangan membuka issue publik untuk vulnerability.

Kirim laporan melalui email:
- security@aliziodev.com

Sertakan informasi berikut:
- Ringkasan masalah
- Dampak potensial
- Langkah reproduksi
- Versi package (`aliziodev/payid`) dan versi PHP/Laravel
- Bukti konsep (PoC) jika ada

## What to Expect

- Konfirmasi awal: maksimal 3 hari kerja
- Triage awal: maksimal 7 hari kerja
- Jika valid, kami akan menyiapkan patch dan advisory
- Disclosure publik dilakukan setelah patch tersedia

## Scope Guidance

Contoh area sensitif yang kami prioritaskan:
- Webhook verification bypass
- Credential leakage (config/logs/exceptions)
- Request signing / signature validation flaws
- Unsafe default behavior pada payment state transition

## Safe Harbor

Kami mendukung responsible disclosure yang dilakukan dengan itikad baik dan tanpa merusak data pengguna.
