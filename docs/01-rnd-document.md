# PayID — R&D Document
> Unified Laravel Payment Orchestrator for Indonesian Payment Gateways
> Version: 1.0.0-draft | Date: 2026-04-13

---

## 1. Ringkasan Eksekutif

**PayID** adalah package Laravel yang berperan sebagai **payment orchestration core** untuk berbagai payment gateway Indonesia. Package ini menyediakan satu lapisan abstraksi yang seragam di atas implementasi teknis masing-masing provider, sehingga developer tidak perlu menulis integrasi dari nol setiap kali menggunakan provider berbeda.

Filosofi utama PayID: **satu API, banyak provider, tanpa coupling**.

---

## 2. Analisa Masalah

### 2.1 Kondisi Saat Ini

Developer Laravel yang bekerja dengan payment gateway Indonesia menghadapi tantangan berulang:

- Setiap provider (Midtrans, Xendit, DOKU, iPaymu, dll) memiliki struktur API, request, dan response yang berbeda-beda.
- Format webhook, mekanisme signature, dan model status transaksi tidak seragam antar provider.
- Setiap pergantian provider membutuhkan perubahan kode yang cukup besar di application layer.
- Logic payment tersebar di berbagai tempat dalam satu aplikasi, mempersulit testing dan maintenance.
- Tidak ada standar enum untuk status dan channel payment — setiap developer mendefinisikan sendiri.

### 2.2 Dampak Nyata

| Masalah | Dampak |
|---|---|
| API provider berbeda-beda | Waktu development lebih lama |
| Tidak ada abstraction standar | Coupling tinggi ke provider tertentu |
| Webhook tidak seragam | Rawan bug dan sulit diuji |
| Logic tersebar | Maintenance mahal |
| Tidak ada standar status/channel | Analytics dan reporting sulit dikerjakan |

### 2.3 Akar Masalah

Akar masalahnya bukan pada provider — setiap provider memang punya kepentingan dan API-nya sendiri. Masalahnya adalah **tidak ada lapisan mediasi yang bersih** antara application layer dan provider layer. PayID hadir untuk menjadi lapisan mediasi tersebut.

---

## 3. Mengapa Package Laravel

Pendekatan package Laravel (bukan standalone library, bukan framework lain) dipilih karena:

1. **Ekosistem Laravel sudah matang** untuk package development — service provider, config publish, facade, auto-discovery, container, event system semuanya tersedia sebagai fondasi yang solid.
2. **Target user sudah bekerja di Laravel** — developer tidak perlu belajar paradigma baru; PayID terasa native.
3. **Laravel menyediakan primitif yang dibutuhkan** — HTTP client, event dispatching, queue, logging, environment config — semuanya siap dipakai tanpa perlu di-reinvent.
4. **Ekosistem package Laravel** memungkinkan distribusi, versioning, dan update yang bersih lewat Composer.
5. **Driver extension model** di Laravel (mirip `illuminate/filesystem`, `illuminate/mail`) sudah terbukti sebagai pola yang scalable untuk orchestrator seperti ini.

---

## 4. Mengapa Core + Driver Architecture

Dua pilihan utama dalam perancangan PayID:

### Opsi A: Single Package Monolitik

Semua provider bundled dalam satu package.

**Kelebihan:**
- Instalasi lebih mudah (`composer require aliziodev/payid`)
- Semua provider langsung tersedia

**Kekurangan:**
- Package membesar cepat seiring bertambahnya provider
- Setiap provider membawa dependency-nya sendiri (SDK resmi, HTTP client spesifik)
- Satu bug di driver X bisa memblokir release untuk semua driver
- Developer yang hanya butuh satu provider tetap harus menanggung semua dependency
- Coupling antara core logic dan provider-specific logic sangat tinggi

### Opsi B: Core + Driver Packages (Dipilih)

Core sebagai orchestrator, setiap provider sebagai package terpisah.

**Kelebihan:**
- Core tetap ringan dan stabil
- Driver bisa dirilis dan diverifikasi secara independen
- Dependency tiap driver terisolasi
- Penambahan provider baru tidak menyentuh core
- Release cycle lebih fleksibel
- Kontrak yang jelas antara core dan driver
- Open untuk third-party driver

**Kekurangan:**
- Developer perlu install dua package (core + driver)
- Butuh disiplin dalam menjaga kontrak antara core dan driver

**Keputusan: Gunakan Core + Driver Architecture.**

Trade-off instalasi yang sedikit lebih panjang sangat worth it dibanding keuntungan jangka panjang dalam hal modularitas, maintainability, dan skalabilitas.

---

## 5. Batasan dan Risiko Desain

### 5.1 Risiko Over-Abstraction

**Masalah:** Core menjadi terlalu generik hingga tidak bisa menangani fitur nyata provider dengan baik.

**Mitigasi:**
- Core hanya mengabstraksikan use case yang benar-benar universal (charge, status, webhook)
- Raw response provider selalu disimpan dan bisa diakses developer
- Provider-specific extensions boleh ada di driver layer

### 5.2 Risiko Fat Core

**Masalah:** Logic provider-specific ditarik ke core, membuat core membengkak.

**Mitigasi:**
- Buat boundary eksplisit: core TIDAK boleh import atau mengasumsikan detail provider tertentu
- Review berkala untuk memastikan tidak ada "provider smell" di core

### 5.3 Risiko Inconsistent Driver Quality

**Masalah:** Setiap driver berkembang dengan gaya berbeda karena tidak ada standar yang jelas.

**Mitigasi:**
- Contract test untuk semua driver
- Driver authoring guide sebagai dokumen wajib
- Code review standard untuk driver PR

### 5.4 Risiko Webhook Complexity

**Masalah:** Webhook tiap provider berbeda format, signature mechanism, dan timing.

**Mitigasi:**
- Webhook pipeline dipisah menjadi tahapan yang eksplisit
- Fixture-based test untuk setiap provider webhook
- Logging wajib di setiap tahap pipeline

### 5.5 Risiko Feature Creep

**Masalah:** Scope melebar ke payout, subscription, settlement, dll. sebelum core stabil.

**Mitigasi:**
- MVP definition yang ketat
- Roadmap bertahap yang jelas
- Non-goals yang terdokumentasi

---

## 6. Prinsip Maintainability Jangka Panjang

Agar PayID dapat bertahan dan berkembang selama bertahun-tahun:

1. **Public API harus kecil dan stabil.** Semakin besar public API, semakin mahal biaya backward compatibility.
2. **Kontrak (interface) harus dijaga ketat.** Breaking change pada interface = semua driver harus update.
3. **Enumerasi (enum) harus diperlakukan sebagai kontrak.** Menghapus enum value = breaking change.
4. **Versioning semantik yang disiplin.** MAJOR untuk breaking changes, MINOR untuk fitur baru, PATCH untuk bug fixes.
5. **Dokumentasi adalah kode, bukan afterthought.** Setiap public API harus terdokumentasi sebelum dianggap selesai.
6. **Testing adalah pagar keamanan.** Tanpa test coverage yang baik, refactoring berbahaya.
7. **Observability harus built-in.** Logging dan event dispatching harus ada sejak awal, bukan ditambahkan belakangan.

---

## 7. Domain yang Diselesaikan PayID

PayID bukan payment gateway. PayID bukan SDK provider. PayID adalah:

- **Orchestrator** — mengkoordinasikan permintaan dari application ke provider yang tepat
- **Normalizer** — mengubah response provider ke format yang seragam
- **Abstraction layer** — menyembunyikan detail teknis provider dari application layer
- **Webhook processor** — menerima, memverifikasi, mengurai, dan menormalkan callback provider
- **Event dispatcher** — meneruskan perubahan status ke sistem event Laravel

Yang PayID **bukan**:
- Pengganti SDK resmi provider
- Payment gateway itu sendiri
- Dashboard atau admin panel
- Settlement / reconciliation engine
- Fraud detection engine

---

## 8. Target Pengguna

### Primary Users
- Laravel developer yang bekerja dengan payment gateway Indonesia
- Backend engineer di startup, agency, atau software house
- Tim yang sering membangun project berbeda dengan kebutuhan payment serupa

### Secondary Users
- Technical lead yang mengevaluasi arsitektur payment
- QA engineer yang perlu menulis integration test untuk payment flow
- Contributor yang ingin membuat driver untuk provider baru

### User Goals
| User | Goal |
|---|---|
| Developer | Integrasi payment cepat tanpa banyak boilerplate |
| Tech Lead | Arsitektur yang clean dan mudah di-maintain |
| Agency | Reusable solution untuk banyak client project |
| QA Engineer | Kemudahan testing tanpa hit payment gateway nyata |

---

## 9. Non-Goals

Hal-hal berikut **bukan** target PayID:

- Membuat payment gateway baru
- Membuat dashboard payment admin
- Menggantikan seluruh fitur proprietary dari masing-masing provider
- Membangun settlement/reconciliation engine
- Membangun reporting/analytics finansial
- Mendukung payout/disbursement di versi awal
- Mendukung subscription management universal di versi awal
- Mendukung split payment universal di versi awal
- Membangun front-end payment widget
- Mendukung non-Indonesia payment provider di versi awal

---

## 10. Success Criteria

### Technical Success
- Driver baru dapat dibuat mengikuti contract yang ada dengan effort yang terprediksi
- Test coverage tinggi di area contract, webhook pipeline, dan DTO mapping
- Zero breaking change pada public API antara minor version
- Tidak ada duplikasi logic antara core dan driver

### Product Success
- Developer dapat mulai menerima payment dengan provider baru dalam waktu minimal
- Perpindahan provider tidak memerlukan perubahan signifikan di application layer
- Dokumentasi cukup untuk onboarding tanpa membutuhkan bantuan pembuat langsung

### Maintenance Success
- Bug di satu driver tidak mempengaruhi driver lain atau core
- Contributor baru dapat memahami arsitektur dalam waktu singkat
- Setiap rilis memiliki changelog yang jelas

---

## 11. Pertanyaan Terbuka (Open Questions)

Pertanyaan berikut perlu dijawab sebelum atau selama implementasi:

1. Apakah PayID hanya fokus pada payment-in atau juga payout/disbursement di masa depan?
2. Apakah driver akan menggunakan SDK resmi provider atau HTTP adapter sendiri?
3. Apakah konfigurasi multi-tenant akan dikelola oleh package atau diserahkan ke app layer?
4. Apakah webhook processing default akan bersifat synchronous atau queueable?
5. Bagaimana kebijakan versioning antara core package dan driver package?
6. Bagaimana strategi backward compatibility untuk enum/status/channel jika provider bertambah?
7. Apakah PayID akan mendukung provider-specific extensions di luar core contract?

---

## 12. Kesimpulan R&D

PayID memiliki peluang nyata untuk menjadi fondasi payment orchestration di ekosistem Laravel Indonesia. Keberhasilan proyek ini bergantung pada:

- **Ketegasan boundary** antara core dan driver — ini adalah invariant terpenting
- **Konsistensi kontrak publik** — sekali diterbitkan, kontrak harus dijaga
- **Disiplin dokumentasi** — dokumentasi yang baik adalah multiplier untuk adopsi
- **Kualitas webhook dan testing** — area ini paling rawan bug dan paling butuh perhatian
- **Fokus pada MVP yang realistis** — lebih baik sedikit fitur tapi solid daripada banyak fitur tapi fragile

Jika dibangun dengan prinsip clean, modern, modular, dan terdokumentasi dengan baik, PayID dapat menjadi package yang reusable lintas project, mudah diskalakan, mudah dirawat, dan nyaman diadopsi banyak developer.
