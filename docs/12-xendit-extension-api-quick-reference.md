# Xendit Extension API Quick Reference

Dokumen ringkas ini berisi method extension pada `XenditDriver` yang tidak termasuk kontrak manager/facade PayID standar.

## 1) Cara akses driver extension

```php
/** @var \Aliziodev\PayIdXendit\XenditDriver $driver */
$driver = PayId::driver('xendit')->getDriver();
```

## 2) PaymentMethod

```php
$paymentMethod = $driver->createPaymentMethod([
    'type' => 'EWALLET',
    'reusability' => 'ONE_TIME_USE',
]);

$paymentMethodDetail = $driver->getPaymentMethod((string) $paymentMethod['id']);
```

## 3) PaymentRequest

```php
$paymentRequest = $driver->createPaymentRequest([
    'reference_id' => 'PR-ORDER-1001',
    'amount' => 175000,
    'currency' => 'IDR',
    'payment_method_id' => 'pm-xxxx',
], 'idem-pr-1001');

$paymentRequestDetail = $driver->getPaymentRequest((string) $paymentRequest['id']);
```

## 4) Customer

```php
$customer = $driver->createCustomer([
    'reference_id' => 'CUST-1001',
    'individual_detail' => [
        'given_names' => 'Budi',
    ],
], 'idem-cust-1001');

$customerDetail = $driver->getCustomer((string) $customer['id']);
```

## 5) Payout

```php
$payout = $driver->createPayout([
    'reference_id' => 'PAYOUT-1001',
    'channel_code' => 'ID_BCA',
    'channel_properties' => [
        'account_number' => '1234567890',
        'account_holder_name' => 'Alizio',
    ],
    'amount' => 100000,
    'currency' => 'IDR',
], 'idem-payout-1001');

$payoutDetail = $driver->getPayout((string) $payout['id']);
```

## 6) Balance

```php
$balance = $driver->getBalance('CASH', 'IDR');
```

## 7) Transaction

```php
$transaction = $driver->getTransaction('txn_123e4567-e89b-42d3-a456-426614174000');
$transactions = $driver->listTransactions('ORDER-1001', 20);
```

## 8) API manager/facade yang tetap diprioritaskan

Untuk flow generik lintas driver, tetap gunakan API standar PayID:

- `charge(...)`
- `status(...)`
- `refund(...)`
- webhook pipeline PayID

Extension method dipakai saat Anda memang butuh fitur Xendit yang lebih spesifik.