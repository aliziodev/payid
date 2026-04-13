# ADR 001 — Core dan Driver Dipisah sebagai Package Terpisah

**Status:** Accepted
**Date:** 2026-04-13

---

## Konteks

PayID perlu mendukung banyak payment provider Indonesia (Midtrans, Xendit, DOKU, iPaymu, dll).
Ada dua pendekatan utama:

- **Monolitik:** semua provider dibundel dalam satu package
- **Core + Driver:** core sebagai package terpisah, setiap provider sebagai package tersendiri

## Keputusan

**Gunakan Core + Driver Architecture.**

Core package (`aliziodev/payid`) hanya berisi contracts, DTO, enums, manager, webhook pipeline,
dan testing utilities. Setiap provider diimplementasikan sebagai package terpisah
(`aliziodev/payid-midtrans`, `aliziodev/payid-xendit`, dst).

## Alasan

1. **Dependency isolation** — developer yang hanya butuh Xendit tidak perlu menanggung
   dependency SDK Midtrans, dan sebaliknya.
2. **Release independence** — bug fix di driver Midtrans tidak mengharuskan release ulang
   seluruh package beserta semua driver lainnya.
3. **Clear boundary** — core tidak boleh tahu detail internal provider; pemisahan package
   menegaskan boundary ini secara struktural, bukan hanya secara konvensi.
4. **Third-party drivers** — komunitas bisa membuat driver untuk provider lain tanpa
   harus fork atau memodifikasi core.
5. **Scalability** — menambah 10 provider baru tidak membuat core menjadi lebih berat.

## Konsekuensi

- Developer perlu install dua package (core + driver), bukan satu.
- Contract antara core dan driver harus dijaga dengan sangat disiplin.
- Versioning antara core dan driver perlu dikomunikasikan dengan jelas di setiap README driver.

## Alternatif yang Ditolak

**Monolitik:** Ditolak karena dependency bloat, release coupling, dan sulitnya maintenance
seiring bertambahnya provider.
