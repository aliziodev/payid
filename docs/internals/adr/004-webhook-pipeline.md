# ADR 004 — Webhook sebagai Pipeline Eksplisit dengan Event Dispatch

**Status:** Accepted
**Date:** 2026-04-13

---

## Konteks

Webhook dari payment provider perlu ditangani dengan cara yang aman, reliable, dan mudah diobservasi.
Ada beberapa pendekatan:

- **Magic callback:** satu method `handleWebhook()` di driver yang melakukan segalanya
- **Pipeline eksplisit:** tahapan verifikasi → parsing → normalisasi → event dispatch dipisah secara jelas
- **Queue-first:** langsung push ke queue tanpa verifikasi synchronous

## Keputusan

**Gunakan pipeline eksplisit dengan event dispatch, dengan opsi queue.**

```
Request masuk → Verifikasi signature → Parse payload → Normalisasi
             → Dispatch WebhookReceived event → Return HTTP 200
```

Setiap tahap dilakukan oleh komponen terpisah yang dapat diuji secara isolated.
Dispatch event ke queue bersifat opsional (dikonfigurasi di `payid.webhook.queue`).

## Alasan

1. **Keamanan** — verifikasi signature adalah tahap pertama dan wajib dilakukan sebelum
   memproses payload apapun. Pipeline eksplisit memastikan ini tidak bisa di-skip.
2. **Observability** — logging di setiap tahap memudahkan debugging: "apakah webhook masuk?",
   "apakah signature valid?", "apakah parsing berhasil?".
3. **Separation of concerns** — verifikasi signature tidak perlu tahu tentang normalisasi;
   normalisasi tidak perlu tahu tentang event dispatch.
4. **Testability** — setiap tahap dapat diuji dengan input/output yang jelas.
5. **Error isolation** — jika parsing gagal, kita tahu persis di tahap mana masalahnya.
6. **Extensibility** — tahap baru bisa ditambahkan tanpa mengubah tahap yang sudah ada.

## Detail Implementasi

- Verifikasi menggunakan **raw request body** (`$request->getContent()`), bukan JSON parsed.
  Ini penting karena beberapa provider menghitung signature dari raw body.
- Jika verifikasi gagal → return 401 + dispatch `WebhookVerificationFailed`
- Jika parsing gagal → return 422 + dispatch `WebhookParsingFailed`
- Jika sukses → return 200 + dispatch `WebhookReceived`
- Webhook controller adalah thin controller yang hanya delegate ke `WebhookProcessor`

## Konsekuensi

- Application layer tidak perlu tahu detail pipeline — cukup listen `WebhookReceived`.
- Idempotency tetap menjadi tanggung jawab application layer (PayID tidak menyimpan state webhook).
- Untuk proses yang lama, gunakan `payid.webhook.queue = true`.

## Alternatif yang Ditolak

**Magic callback:** Ditolak karena sulit diobservasi, diuji, dan di-debug. Error di tengah
proses tidak memberikan informasi yang jelas tentang tahap mana yang gagal.

**Queue-first tanpa verifikasi synchronous:** Ditolak karena memungkinkan payload yang
belum terverifikasi masuk ke queue. Verifikasi signature harus synchronous.
