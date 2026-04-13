# Upgrade Guide

Dokumen ini berisi panduan upgrade antar versi mayor PayID.

## From 0.x to 1.x

Belum ada langkah khusus untuk saat ini.

Gunakan checklist berikut saat upgrade:

1. Update dependency:

```bash
composer update aliziodev/payid
```

2. Publish ulang config jika diperlukan, lalu review perubahan:

```bash
php artisan vendor:publish --tag=payid-config --force
```

3. Verifikasi environment key utama tetap benar:
- `PAYID_DEFAULT_DRIVER`
- kredensial tiap driver di `.env`

4. Jalankan quality gates sebelum deploy:

```bash
composer test
vendor/bin/phpstan
```

5. Untuk production, review checklist:
- [docs/06-production-readiness.md](docs/06-production-readiness.md)

## Backward Compatibility Policy

- Perubahan breaking hanya dilakukan saat major release
- Perubahan non-breaking dirilis di minor/patch
- Deprecation akan diumumkan di changelog sebelum dihapus
