# Production Readiness Checklist

Dokumen ini membantu memastikan integrasi PayID aman, stabil, dan siap production.

## 1. Environment dan Konfigurasi

- Pastikan driver default benar:
  - `PAYID_DEFAULT_DRIVER=midtrans` (atau provider lain)
- Pastikan semua credential provider tersedia di environment production.
- Jangan menyimpan credential langsung di source code.
- Jika menggunakan config cache, jalankan:
  - `php artisan config:cache`

## 2. Webhook Security

- Daftarkan endpoint webhook sesuai driver:
  - `/payid/webhook/{driver}`
- Pastikan verifikasi signature webhook aktif di driver.
- Gunakan HTTPS dan batasi akses endpoint webhook via WAF/rate limiting.
- Implementasikan idempotency di sisi aplikasi saat memproses event webhook.

## 3. Error Handling dan Observability

- Tangani exception utama dari PayID:
  - `ProviderApiException`
  - `ProviderNetworkException`
  - `UnsupportedCapabilityException`
  - `MissingDriverConfigException`
- Aktifkan logging (`payid.logging.enabled=true`) dan pilih channel log yang sesuai.
- Mask data sensitif saat logging payload request/response.

## 4. Operasional Payment Flow

- Simpan `merchant_order_id` yang unik dan konsisten.
- Status order sebaiknya ditentukan dari webhook sebagai sumber kebenaran utama.
- Gunakan endpoint status (`PayId::status`) untuk reconciliation/backfill.
- Untuk provider tertentu, validasi capability sebelum memakai fitur (`refund`, `cancel`, dll).

## 5. Testing Minimum Sebelum Go-Live

- Jalankan test suite package:
  - `composer test`
- Jalankan static analysis:
  - `vendor/bin/phpstan`
- Tambahkan integration test di aplikasi host untuk:
  - happy path charge
  - webhook paid
  - webhook invalid signature
  - fallback saat network timeout

## 6. Deployment Checklist

- Credential production sudah diisi dan diverifikasi.
- URL callback/webhook di dashboard provider mengarah ke domain production.
- Queue worker aktif jika pemrosesan webhook dilakukan async di aplikasi host.
- Monitoring dan alert untuk kegagalan webhook/error payment sudah disiapkan.

## 7. Post-Deployment Checks

- Lakukan smoke test transaksi nominal kecil di production.
- Validasi sinkronisasi status antara dashboard provider dan database aplikasi.
- Review log 24 jam pertama untuk error pattern (network/API/verification).
