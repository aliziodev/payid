# Nicepay Extension API Quick Reference

Dokumen ini merangkum extension method `NicepayDriver` untuk fitur di luar API generic manager PayID.

## 1) SNAP API

### Access Token

- `snapAccessToken(array $payload): array`

### Virtual Account

- `snapVaGenerate(array $payload): array`
- `snapVaInquiry(array $payload): array`
- `snapVaCancel(array $payload): array`

### Ewallet

- `snapEwalletPayment(array $payload): array`
- `snapEwalletInquiry(array $payload): array`
- `snapEwalletRefund(array $payload): array`

### QRIS

- `snapQrisGenerate(array $payload): array`
- `snapQrisInquiry(array $payload): array`
- `snapQrisRefund(array $payload): array`

### Payout

- `snapPayoutRegistration(array $payload): array`
- `snapPayoutApprove(array $payload): array`
- `snapPayoutInquiry(array $payload): array`
- `snapPayoutBalance(array $payload = []): array`
- `snapPayoutCancel(array $payload): array`
- `snapPayoutReject(array $payload): array`

## 2) V2 API

### VA

- `v2VaRegistration(array $payload): array`
- `v2VaInquiry(array $payload): array`
- `v2VaCancel(array $payload): array`

### CVS

- `v2CvsRegistration(array $payload): array`
- `v2CvsInquiry(array $payload): array`
- `v2CvsCancel(array $payload): array`

### Payloan

- `v2PayloanRegistration(array $payload): array`
- `v2PayloanInquiry(array $payload): array`
- `v2PayloanCancel(array $payload): array`

### QRIS

- `v2QrisRegistration(array $payload): array`
- `v2QrisInquiry(array $payload): array`
- `v2QrisCancel(array $payload): array`

### Card

- `v2CardRegistration(array $payload): array`
- `v2CardInquiry(array $payload): array`
- `v2CardCancel(array $payload): array`
- `v2CardPayment(array $payload): array`

### Ewallet

- `v2EwalletRegistration(array $payload): array`
- `v2EwalletInquiry(array $payload): array`
- `v2EwalletCancel(array $payload): array`
- `v2EwalletPayment(array $payload): array`

## 3) Utility

- `verifySignatureSha256(string $dataString, string $signatureBase64, string $publicKey): bool`

## 4) Penggunaan aman di aplikasi multi-driver

- Hindari hard-cast driver sebelum memastikan driver yang aktif memang `nicepay`.
- Simpan adapter/service layer di aplikasi agar payload SNAP/V2 tetap terisolasi.
- Untuk operasi non-generic, dokumentasikan fallback behavior saat driver berganti.
