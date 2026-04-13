# Nicepay Complete Usage Guide

Dokumen ini merangkum cara pakai driver Nicepay pada PayID, dari flow generic manager sampai extension API Nicepay (SNAP + V2).

## 1) Instalasi

```bash
composer require aliziodev/payid
composer require aliziodev/payid-nicepay
```

## 2) Konfigurasi

Tambahkan variabel environment:

```env
PAYID_DEFAULT_DRIVER=nicepay

NICEPAY_ENV=sandbox
NICEPAY_MERCHANT_ID=
NICEPAY_CLIENT_SECRET=
NICEPAY_PRIVATE_KEY=
NICEPAY_MERCHANT_KEY=
NICEPAY_PARTNER_ID=
NICEPAY_BASE_URL=
NICEPAY_TIMEOUT=30

NICEPAY_WEBHOOK_VERIFY=false
NICEPAY_WEBHOOK_TOKEN=
NICEPAY_WEBHOOK_PUBLIC_KEY=

NICEPAY_PAYMENT_PATH=/api/v1.0/debit/payment-host-to-host
NICEPAY_STATUS_PATH=/api/v1.0/debit/status
```

## 3) Flow generic via manager/facade

### Charge

```php
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Facades\PayId;

$request = ChargeRequest::make([
    'merchant_order_id' => 'ORD-10001',
    'amount' => 150000,
    'currency' => 'IDR',
    'channel' => PaymentChannel::BankTransfer,
    'description' => 'Pembayaran order ORD-10001',
    'customer' => [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'phone' => '08123456789',
    ],
    'success_url' => 'https://app.test/payment/success',
    'callback_url' => 'https://app.test/webhook/nicepay',
]);

$response = PayId::driver('nicepay')->charge($request);
```

### Status

```php
$status = PayId::driver('nicepay')->status('ORD-10001');
```

### Webhook parse + verify

```php
use Illuminate\Http\Request;
use Aliziodev\PayId\Facades\PayId;

public function webhook(Request $request)
{
    $driver = PayId::driver('nicepay');

    if (! $driver->verifyWebhook($request)) {
        abort(401, 'Invalid webhook signature');
    }

    $normalized = $driver->parseWebhook($request);

    // $normalized->status sudah dinormalisasi ke enum status PayID
    return response()->json(['ok' => true]);
}
```

## 4) Extension API Nicepay (SNAP + V2)

Akses extension dilakukan dari instance driver konkret:

```php
$driver = app('payid')->driver('nicepay');
```

Contoh:

```php
// SNAP VA
$driver->snapVaGenerate([
    'partnerReferenceNo' => 'ORD-10001',
    'amount' => ['value' => '150000.00', 'currency' => 'IDR'],
]);

// SNAP QRIS inquiry
$driver->snapQrisInquiry([
    'originalPartnerReferenceNo' => 'ORD-10001',
]);

// V2 Card payment
$driver->v2CardPayment([
    'iMid' => env('NICEPAY_MERCHANT_ID'),
    'referenceNo' => 'ORD-10001',
]);
```

## 5) Rekomendasi implementasi

- Gunakan API generic (`charge`, `status`, webhook normalized) sebagai entry point default aplikasi.
- Panggil extension method hanya untuk kebutuhan domain khusus Nicepay.
- Lindungi pemanggilan fitur opsional dengan capability check jika aplikasi multi-driver.
- Simpan mapping payload-per-channel di service aplikasi agar perubahan API provider tidak menyebar ke banyak tempat.

## 6) Referensi

- Nicepay docs: https://docs.nicepay.co.id
- SDK official: https://github.com/nicepay-dev/php-nicepay
