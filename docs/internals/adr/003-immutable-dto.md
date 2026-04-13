# ADR 003 — DTO Immutable dengan PHP 8.2 Readonly Properties

**Status:** Accepted
**Date:** 2026-04-13

---

## Konteks

PayID menggunakan DTO (Data Transfer Objects) untuk membawa data antar layer:
`ChargeRequest`, `ChargeResponse`, `StatusResponse`, `NormalizedWebhook`, dll.

Ada dua pendekatan:

- **Mutable DTO:** properties bisa diubah setelah objek dibuat
- **Immutable DTO:** properties tidak bisa diubah setelah objek dibuat (readonly)

## Keputusan

**Gunakan immutable DTO dengan PHP 8.2 `readonly` properties.**

```php
final class ChargeRequest
{
    public function __construct(
        public readonly string $merchantOrderId,
        public readonly int $amount,
        // ...
    ) {}
}
```

## Alasan

1. **Predictability** — DTO yang masuk ke driver tidak bisa diubah di tengah jalan
   oleh kode lain. Data yang dibuat aplikasi adalah data yang diterima driver.
2. **Thread safety** — immutable objects aman digunakan di konteks concurrent atau queue.
3. **Clarity of intent** — `readonly` secara eksplisit mengkomunikasikan bahwa objek ini
   adalah transport object, bukan entity yang dimodifikasi.
4. **Bug prevention** — tidak ada risiko driver secara tidak sengaja mengubah data request
   yang kemudian mempengaruhi logic lain.
5. **PHP 8.2 native support** — tidak perlu trick manual untuk immutability.

## Konsekuensi

- DTO tidak bisa dimodifikasi setelah dibuat. Jika perlu perubahan, buat instance baru.
- Named constructor `make()` diperlukan untuk kemudahan pembuatan dari array.
- Tidak bisa menggunakan DTO sebagai form object yang diisi secara bertahap.

## Alternatif yang Ditolak

**Mutable DTO:** Ditolak karena memungkinkan perubahan data yang tidak terduga di berbagai
titik alur, mempersulit debugging dan tracing data flow.
