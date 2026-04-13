# Midtrans Extension API Quick Reference

Dokumen ringkas ini berisi method extension pada `MidtransDriver` yang tidak termasuk kontrak manager/facade PayID standar.

## 1) Cara akses driver extension

```php
/** @var \Aliziodev\PayIdMidtrans\MidtransDriver $driver */
$driver = PayId::driver('midtrans')->getDriver();
```

## 2) Snap-BI

```php
$b2bStatus = $driver->getSnapBiTransactionStatus('ORDER-1001');
```

## 3) Payment Link

```php
$paymentLink = $driver->createPaymentLink([
    'transaction_details' => [
        'order_id' => 'ORDER-LINK-1001',
        'gross_amount' => 175000,
    ],
]);

$paymentLinkDetail = $driver->getPaymentLink('ORDER-LINK-1001');
$paymentLinkDelete = $driver->deletePaymentLink('ORDER-LINK-1001');
```

## 4) Balance Mutation

```php
$balanceMutation = $driver->getBalanceMutation(
    'IDR',
    '2026-04-01 00:00:00',
    '2026-04-14 23:59:59'
);
```

## 5) Invoicing

```php
$invoice = $driver->createInvoice([
    'external_id' => 'INV-1001',
    'payer_email' => 'budi@example.com',
    'description' => 'Invoice test',
    'amount' => 200000,
]);

$invoiceDetail = $driver->getInvoice((string) $invoice['id']);
$invoiceVoid = $driver->voidInvoice((string) $invoice['id']);
```

## 6) GoPay Account Linking

```php
use Aliziodev\PayId\DTO\GopayAccountLinkRequest;

$gopayAccount = $driver->linkGopayAccount(GopayAccountLinkRequest::make([
    'partner_reference_no' => 'GOPAY-LINK-1001',
    'phone_number' => '081234567890',
]));
```

## 7) API manager/facade yang tetap diprioritaskan

Untuk flow generik lintas driver, tetap gunakan API standar PayID:

- `charge(...)`
- `status(...)`
- `refund(...)`
- lifecycle operation (`cancel`, `expire`, `approve`, `deny`)
- subscription operation (`createSubscription`, `getSubscription`, `updateSubscription`, `pauseSubscription`, `resumeSubscription`, `cancelSubscription`)
- webhook pipeline PayID

Extension method dipakai saat Anda butuh fitur Midtrans yang spesifik dan tidak ada di kontrak manager/facade umum.
