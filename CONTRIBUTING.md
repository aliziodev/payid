# Contributing

Terima kasih ingin berkontribusi ke PayID.

## Development Setup

1. Install dependencies:

```bash
composer install
```

2. Jalankan test:

```bash
composer test
```

3. Jalankan static analysis:

```bash
vendor/bin/phpstan
```

4. Cek coding style:

```bash
vendor/bin/pint --test
```

## Pull Request Guidelines

- Fokus satu topik perubahan per PR
- Tambahkan/ubah test untuk behavior baru
- Hindari breaking change tanpa diskusi lebih dulu
- Update dokumentasi jika API/perilaku berubah

## Commit Message (Recommended)

Gunakan format ringkas yang jelas, contoh:
- `feat: add webhook controller integration tests`
- `fix: align install command env key with config`
- `docs: refresh README testing examples`

## Reporting Bugs

Saat membuka issue, sertakan:
- versi package
- versi PHP/Laravel
- langkah reproduksi
- expected vs actual result
- log/error relevan

## Security Issues

Untuk kerentanan keamanan, ikuti kebijakan di [SECURITY.md](SECURITY.md).
