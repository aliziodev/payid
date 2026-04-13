# ADR 002 — Capability-Based Contracts (Interface Kecil, Bukan Interface Besar)

**Status:** Accepted
**Date:** 2026-04-13

---

## Konteks

Tidak semua payment provider mendukung fitur yang sama. Midtrans mendukung refund,
tapi tidak semua provider mendukung expire transaction. Beberapa provider tidak memiliki
mekanisme cancel yang eksplisit.

Ada dua pendekatan untuk kontrak driver:

- **Single fat interface:** satu interface besar dengan semua method yang mungkin dibutuhkan
- **Capability-based interfaces:** banyak interface kecil, masing-masing untuk satu capability

## Keputusan

**Gunakan capability-based interfaces.**

```
DriverInterface    — wajib untuk semua driver
SupportsCharge     — driver yang mendukung pembuatan transaksi
SupportsStatus     — driver yang mendukung cek status
SupportsRefund     — driver yang mendukung refund
SupportsCancel     — driver yang mendukung cancel
SupportsExpire     — driver yang mendukung expire
SupportsWebhookVerification — driver yang bisa verifikasi webhook
SupportsWebhookParsing      — driver yang bisa parse webhook
```

## Alasan

1. **Kejujuran terhadap capability provider** — driver tidak perlu mengimplementasikan method
   yang providernya tidak mendukung hanya untuk memenuhi satu interface besar.
2. **Type safety yang eksplisit** — core dapat mengecek `$driver instanceof SupportsRefund`
   dan melempar `UnsupportedCapabilityException` yang jelas jika tidak supported.
3. **Open/Closed Principle** — menambah capability baru (misalnya `SupportsPayout`) tidak
   mempengaruhi driver yang sudah ada.
4. **Testability** — setiap capability dapat diuji secara isolated.
5. **Documentation clarity** — dari daftar interface yang diimplementasikan driver,
   langsung jelas apa saja yang didukungnya.

## Konsekuensi

- Lebih banyak file interface, tapi masing-masing sangat kecil dan fokus.
- Driver harus mendeklarasikan capabilities-nya secara eksplisit di `getCapabilities()`.
- Core harus selalu cek capability sebelum memanggil method capability-specific.

## Alternatif yang Ditolak

**Single fat interface:** Ditolak karena memaksa driver mengimplementasikan method yang
tidak relevan (biasanya dengan `throw new UnsupportedCapabilityException()`), yang justru
lebih verbose dan kurang informatif dibanding tidak mengimplementasikan interface-nya sama sekali.
