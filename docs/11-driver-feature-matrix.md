# Driver Feature Matrix (Midtrans vs Xendit vs iPaymu vs Nicepay)

Dokumen ini merangkum fitur yang bisa digunakan untuk setiap driver, disesuaikan dengan API provider dan kapabilitas aktual driver saat ini.

## 1) Matriks fitur per driver

Legenda:

- `Yes`: tersedia dan bisa dipanggil via manager/facade PayID.
- `Driver-only`: tersedia di kelas driver spesifik, bukan API umum manager/facade.
- `No`: belum didukung pada driver saat ini.

| Fitur | Midtrans | Xendit | iPaymu | Nicepay | Catatan API Provider |
|---|---|---|---|---|---|
| Charge | Yes | Yes | Yes | Yes | Midtrans: Snap/Core. Xendit: Invoice API. iPaymu: Payment API. Nicepay: payment host-to-host/redirect flow. |
| Direct charge | Yes | No | Yes | No | Midtrans Core API. iPaymu via endpoint direct payment extension. |
| Redirect payment | Yes | Yes | Yes | Yes | Midtrans Snap redirect, Xendit invoice URL, iPaymu redirect alias, Nicepay redirect URL. |
| Status | Yes | Yes | Yes | Yes | Keempatnya tersedia. |
| Refund | Yes | Yes | No | Driver-only | Nicepay refund tersedia di extension method SNAP (ewallet/qris). |
| Cancel | Yes | No | No | Driver-only | Midtrans expose cancel API; Nicepay cancel tersedia via extension SNAP/V2. |
| Expire | Yes | No | No | No | Midtrans expose expire API. |
| Approve | Yes | No | No | No | Relevan untuk flow fraud/challenge Midtrans. |
| Deny | Yes | No | No | No | Relevan untuk flow fraud/challenge Midtrans. |
| Create subscription | Yes | No | No | No | Midtrans subscription API tersedia. |
| Get subscription | Yes | No | No | No | Midtrans subscription API tersedia. |
| Update subscription | Yes | No | No | No | Midtrans subscription API tersedia. |
| Pause subscription | Yes | No | No | No | Midtrans subscription API tersedia. |
| Resume subscription | Yes | No | No | No | Midtrans subscription API tersedia. |
| Cancel subscription | Yes | No | No | No | Midtrans subscription API tersedia. |
| Webhook verification | Yes | Yes | Yes | Yes | Midtrans signature check, Xendit callback token, iPaymu token/signature config, Nicepay token/signature config. |
| Webhook parsing (normalized) | Yes | Yes | Yes | Yes | Dipetakan ke `NormalizedWebhook` PayID. |
| List channels | No | No | Driver-only | No | iPaymu extension method untuk daftar channel aktif. |
| GoPay account linking | Driver-only | No | No | No | Midtrans extension method di `MidtransDriver`. |
| Snap-BI status | Driver-only | No | No | No | Midtrans extension method di `MidtransDriver`. |
| Payment Link | Driver-only | No | No | No | Midtrans extension method di `MidtransDriver`. |
| Balance mutation | Driver-only | No | No | No | Midtrans extension method di `MidtransDriver`. |
| Invoicing | Driver-only | No | No | No | Midtrans extension method di `MidtransDriver`. |
| PaymentMethod | No | Driver-only | No | No | Xendit extension method di `XenditDriver`. |
| PaymentRequest | No | Driver-only | No | No | Xendit extension method di `XenditDriver`. |
| Customer | No | Driver-only | No | No | Xendit extension method di `XenditDriver`. |
| Payout | No | Driver-only | No | Driver-only | Xendit dan Nicepay expose payout via extension method driver. |
| Balance | No | Driver-only | Driver-only | Driver-only | Xendit, iPaymu, dan Nicepay expose balance via extension method. |
| Transaction | No | Driver-only | Driver-only | Driver-only | Xendit transaksi detail/list, iPaymu history transaction, Nicepay inquiry by channel. |
| Virtual account | Driver-only | No | No | Driver-only | Midtrans VA API dan Nicepay SNAP/V2 VA di extension method. |
| QRIS | Driver-only | No | No | Driver-only | Midtrans QRIS dan Nicepay SNAP/V2 QRIS di extension method. |
| Ewallet | Driver-only | No | No | Driver-only | Midtrans ewallet dan Nicepay SNAP/V2 ewallet di extension method. |
| Card | Driver-only | No | No | Driver-only | Midtrans card flow dan Nicepay V2 card di extension method. |

## 2) Fitur yang di-handle oleh PayID Core (lintas driver)

Fitur ini bukan API milik provider tertentu, tapi orkestrasi standar dari PayID agar integrasi antar driver konsisten.

| Fitur Core PayID | Berlaku untuk Midtrans | Berlaku untuk Xendit | Berlaku untuk iPaymu | Berlaku untuk Nicepay | Keterangan |
|---|---|---|---|---|---|
| Driver orchestration via facade/manager | Yes | Yes | Yes | Yes | `PayId::driver('...')` dan API terstandarisasi. |
| Capability guard (`supports`) | Yes | Yes | Yes | Yes | Cek fitur sebelum dipanggil. |
| DTO standar request/response | Yes | Yes | Yes | Yes | `ChargeRequest`, `ChargeResponse`, `StatusResponse`, dll. |
| Canonical payment status mapping | Yes | Yes | Yes | Yes | Status provider dinormalisasi ke enum PayID. |
| Webhook route default | Yes | Yes | Yes | Yes | `POST /{prefix}/webhook/{driver}`. |
| Webhook pipeline | Yes | Yes | Yes | Yes | Verify -> parse -> normalize -> dispatch event. |
| Webhook event dispatch | Yes | Yes | Yes | Yes | `WebhookReceived`, `WebhookVerificationFailed`, `WebhookParsingFailed`. |
| Logging pipeline | Yes | Yes | Yes | Yes | Logging incoming/verify/parse/process di core. |
| Optional ledger integration | Yes | Yes | Yes | Yes | Jika `payid-transactions` terpasang, event dan snapshot status otomatis dicatat. |
| Error normalization | Yes | Yes | Yes | Yes | Error SDK/provider dipetakan ke exception PayID. |

## 3) Rekomendasi penggunaan

- Jika butuh fitur payment paling lengkap (charge, refund, lifecycle control, subscription), pilih Midtrans.
- Jika butuh flow invoice plus operasi finansial Xendit (payment method/request, payout, transaction), pilih Xendit.
- Jika butuh integrasi ringan iPaymu (charge, status, webhook) plus kebutuhan balance/history, gunakan iPaymu driver + extension method.
- Jika butuh coverage API Nicepay yang luas (SNAP + V2) tanpa mengorbankan API generic PayID, gunakan Nicepay driver dan panggil extension method sesuai domain bisnis.
- Untuk aplikasi multi-driver, selalu gunakan `supports(...)` sebelum memanggil API opsional.
