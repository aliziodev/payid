# iPaymu Extension API Quick Reference

Dokumen ringkas ini berisi method extension pada `IpaymuDriver` yang tidak termasuk kontrak manager/facade PayID standar.

## 1) Cara akses driver extension

```php
/** @var \Aliziodev\PayIdIpaymu\IpaymuDriver $driver */
$driver = PayId::driver('ipaymu')->getDriver();
```

## 2) Redirect Payment (iPaymu Payment Page)

Gunakan untuk membuat redirect URL pembayaran iPaymu.

```php
$redirectPayment = $driver->redirectPayment([
    'referenceId' => 'ORDER-5001',
    'amount' => 150000,
    'buyerName' => 'Alizio',
]);
```

Catatan:
- Flow ini juga bisa dicapai lewat API manager `charge(...)` pada driver iPaymu.
- Redirect URL biasanya tersedia pada field `Data.Url` (raw response) atau `payment_url` (hasil normalisasi charge).

## 3) Direct Payment

```php
$directPayment = $driver->directPayment([
    'amount' => 50000,
    'buyerName' => 'Alizio',
    'buyerEmail' => 'budi@example.com',
]);
```

## 4) List Payment Channels

```php
$channels = $driver->listPaymentChannels();

// Alias yang setara:
$channelsAlt = $driver->paymentChannels();
```

## 5) Check Balance

```php
$balance = $driver->checkBalance();
```

## 6) History Transaction

```php
$history = $driver->historyTransaction([
    'limit' => 20,
]);
```

## 7) Callback Params (Success, Pending, Expired)

Gunakan helper ini untuk normalisasi payload callback iPaymu.

```php
$callbackParams = $driver->callbackParams([
    'referenceId' => 'ORDER-5001',
    'transactionId' => 'TRX-5001',
    'status' => 'pending',
    'amount' => 150000,
    'currency' => 'IDR',
]);

// Contoh field hasil normalisasi:
// - reference_id
// - transaction_id
// - status_raw
// - status (mapped ke canonical status PayID: paid|pending|expired|...)
// - amount
// - currency
// - channel_raw
// - occurred_at
// - raw_payload
```

## 8) API manager/facade yang tetap diprioritaskan

Untuk flow generik lintas driver, tetap gunakan API standar PayID:

- `charge(...)`
- `status(...)`
- webhook pipeline PayID

Extension method dipakai saat Anda butuh fitur iPaymu yang spesifik.
