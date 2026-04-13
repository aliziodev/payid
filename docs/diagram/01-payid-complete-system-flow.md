# PayID Complete System Flow Diagram

Dokumen ini merangkum alur end-to-end PayID secara lengkap: operasi manager/facade, routing capability ke driver, webhook pipeline, serta extension flow Midtrans, Xendit, dan iPaymu.

## 1) System context dan boundary

```mermaid
flowchart TD
    A[Application Layer<br/>Controller Job Command Service] --> B[PayId Facade or PayIdManager]
    B --> C{Driver selection}

    C -->|default from config| D[Configured default driver]
    C -->|runtime override| E[PayId driver by name]

    D --> F[DriverFactory]
    E --> F

    F --> G[MidtransDriver]
    F --> H[XenditDriver]
    F --> H2[IpaymuDriver]

    G --> I[Midtrans APIs<br/>Snap Core Subscription Snap-BI<br/>Payment Link Balance Invoice GoPay Link]
    H --> J[Xendit APIs<br/>Invoice Refund PaymentMethod PaymentRequest<br/>Customer Payout Balance Transaction]
    H2 --> J2[iPaymu APIs<br/>Payment Transaction Webhook<br/>Balance History]

    B --> K[WebhookProcessor]
    K --> L[WebhookController route<br/>POST or configured prefix webhook endpoint]

    B --> M[Laravel Events]
    K --> M

    B --> N[(Optional payid-transactions ledger)]
    K --> N
```

## 2) Flow operasi facade/manager ke driver

```mermaid
flowchart TD
    A[App calls PayId API] --> B[PayIdManager resolveDriver]
    B --> C[Load config payid.default atau runtime driver]
    C --> D[Resolve from DriverFactory and cache instance]
    D --> E{supports Capability?}

    E -->|No| F[Throw UnsupportedCapabilityException]
    E -->|Yes| G[Invoke driver method]

    G --> H[Map provider response ke DTO standar]
    H --> I[Dispatch domain event]
    I --> J[Return DTO ke aplikasi]

    I --> K[(Optional ledger update)]
```

## 3) Matrix alur operasi yang bisa dilakukan

| Group | API di PayIdManager (lintas driver) | Capability check | Event core | Ketersediaan Midtrans | Ketersediaan Xendit |
|---|---|---|---|---|---|
| Driver switching | `driver` | N/A | - | Yes | Yes |
| Driver extender | `extend` | N/A | - | Yes | Yes |
| Credential resolver | `resolveCredentialsUsing` | N/A | - | Yes | Yes |
| Payment checkout | `charge` | `charge` | `PaymentCharged` | Yes | Yes |
| Direct charge | `directCharge` | `direct_charge` | `PaymentCharged` | Yes | No |
| Status | `status` | `status` | `PaymentStatusChecked` | Yes | Yes |
| Refund | `refund` | `refund` | `PaymentRefunded` | Yes | Yes |
| Cancel | `cancel` | `cancel` | `PaymentCancelled` | Yes | No |
| Expire | `expire` | `expire` | `PaymentExpired` | Yes | No |
| Approve | `approve` | `approve` | `PaymentApproved` | Yes | No |
| Deny | `deny` | `deny` | `PaymentDenied` | Yes | No |
| Subscription create | `createSubscription` | `create_subscription` | `SubscriptionCreated` | Yes | No |
| Subscription get | `getSubscription` | `get_subscription` | - | Yes | No |
| Subscription update | `updateSubscription` | `update_subscription` | `SubscriptionUpdated` | Yes | No |
| Subscription pause | `pauseSubscription` | `pause_subscription` | `SubscriptionPaused` | Yes | No |
| Subscription resume | `resumeSubscription` | `resume_subscription` | `SubscriptionResumed` | Yes | No |
| Subscription cancel | `cancelSubscription` | `cancel_subscription` | `SubscriptionCancelled` | Yes | No |
| Utility | `supports`, `getDriver` | N/A | - | Yes | Yes |

## 4) Sequence flow operasi pembayaran utama

```mermaid
sequenceDiagram
    participant App as Application
    participant Manager as PayIdManager
    participant Driver as Selected Driver
    participant Provider as Gateway Provider
    participant Events as Laravel Events
    participant Ledger as Optional Ledger

    App->>Manager: charge(ChargeRequest)
    Manager->>Manager: assert supports(charge)
    Manager->>Driver: charge(request)
    Driver->>Provider: provider API request
    Provider-->>Driver: provider response
    Driver-->>Manager: ChargeResponse (normalized)
    Manager->>Ledger: recordChargeAttempt (optional)
    Manager->>Events: dispatch PaymentCharged
    Manager-->>App: ChargeResponse

    App->>Manager: status(orderId)
    Manager->>Manager: assert supports(status)
    Manager->>Driver: status(orderId)
    Driver->>Provider: query status
    Provider-->>Driver: status payload
    Driver-->>Manager: StatusResponse
    Manager->>Ledger: upsert status snapshot (optional)
    Manager->>Events: dispatch PaymentStatusChecked
    Manager-->>App: StatusResponse
```

## 5) Sequence flow webhook pipeline

```mermaid
sequenceDiagram
    participant Provider as Provider Webhook Sender
    participant Route as Webhook Route
    participant Controller as WebhookController
    participant Processor as WebhookProcessor
    participant Driver as Driver
    participant Events as Laravel Events
    participant Ledger as Optional Ledger

    Provider->>Route: POST configured webhook endpoint
    Route->>Controller: invoke(request, driver)
    Controller->>Processor: handle(request, driver)

    Processor->>Ledger: recordWebhookEvent (optional)
    Processor->>Driver: verifyWebhook(request)

    alt verification failed
        Processor->>Events: WebhookVerificationFailed
        Processor->>Ledger: markWebhookProcessed(false)
        Processor-->>Controller: 401 unauthorized
    else verification passed
        Processor->>Driver: parseWebhook(request)

        alt parsing failed
            Processor->>Events: WebhookParsingFailed
            Processor->>Ledger: markWebhookProcessed(false)
            Processor-->>Controller: 422 unprocessable
        else parsed
            Processor->>Ledger: upsertStatusFromWebhook (optional)
            Processor->>Events: WebhookReceived
            Processor->>Ledger: markWebhookProcessed(true)
            Processor-->>Controller: 200 ok
        end
    end
```

## 6) Integrasi antar driver (pola interoperability)

```mermaid
flowchart LR
    A[Order domain di aplikasi] --> B[Build DTO standar PayID]

    B --> C{Pilih driver}
    C --> D[midtrans]
    C --> E[xendit]

    D --> F[Midtrans mapper<br/>DTO to Midtrans payload]
    E --> G[Xendit mapper<br/>DTO to Xendit payload]

    F --> H[Midtrans API response]
    G --> I[Xendit API response]

    H --> J[Normalize ke DTO PayID]
    I --> J

    J --> K[App domain consume DTO standar]
```

Interpretasi:
- Kontrak DTO + enum di core membuat aplikasi tidak perlu tahu bentuk payload proprietary provider.
- Integrasi multi-driver aman selama aplikasi memakai API manager/facade untuk flow generik.
- Untuk fitur provider-specific, aplikasi mengambil driver asli lewat `getDriver()` dan memanggil extension method.

## 7) Driver-specific extension flow yang sudah tersedia

### Midtrans extension (driver-specific)

```mermaid
flowchart TD
    A[PayId driver midtrans then getDriver] --> B[MidtransDriver extension APIs]
    B --> C[getSnapBiTransactionStatus]
    B --> D[createPaymentLink getPaymentLink deletePaymentLink]
    B --> E[getBalanceMutation]
    B --> F[createInvoice getInvoice voidInvoice]
    B --> G[linkGopayAccount]
```

### Xendit extension (driver-specific)

```mermaid
flowchart TD
    A[PayId driver xendit then getDriver] --> B[XenditDriver extension APIs]
    B --> C[createPaymentMethod getPaymentMethod]
    B --> D[createPaymentRequest getPaymentRequest]
    B --> E[createCustomer getCustomer]
    B --> F[createPayout getPayout]
    B --> G[getBalance]
    B --> H[getTransaction listTransactions]
```

### iPaymu extension (driver-specific)

```mermaid
flowchart TD
    A[PayId driver ipaymu then getDriver] --> B[IpaymuDriver extension APIs]
    B --> C[directPayment]
    B --> D[paymentChannels]
    B --> E[checkBalance]
    B --> F[historyTransaction]
```

## 8) Checklist coverage agar tidak ada yang terlewat

| Area | Sudah tercakup di diagram ini | Catatan |
|---|---|---|
| Driver resolution + default/runtime switch | Yes | include factory + cache instance |
| Capability guard + exception path | Yes | include unsupported capability path |
| Payment operations manager | Yes | charge sampai deny |
| Subscription operations manager | Yes | create sampai cancel |
| Event dispatch core | Yes | payment + subscription + webhook events |
| Optional ledger integration | Yes | manager + webhook processor |
| Webhook endpoint dan middleware flow | Yes | route/controller/processor/response code |
| Webhook error branches | Yes | 401 verification failed, 422 parsing failed |
| Midtrans driver-specific extensions | Yes | Snap-BI, Payment Link, Balance, Invoicing, GoPay link |
| Xendit driver-specific extensions | Yes | PaymentMethod, PaymentRequest, Customer, Payout, Balance, Transaction |
| iPaymu driver-specific extensions | Yes | directPayment, paymentChannels, checkBalance, historyTransaction |
| Interoperability antar driver | Yes | DTO standardization + normalization |

## 9) Rekomendasi penggunaan

- Gunakan API manager/facade untuk seluruh use case generik lintas driver.
- Gunakan `supports(...)` sebelum memanggil capability opsional agar aman saat runtime switch.
- Gunakan extension method hanya untuk kebutuhan provider-specific dan encapsulate di service aplikasi agar boundary tetap bersih.
