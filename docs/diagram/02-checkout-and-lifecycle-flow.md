# Checkout and Payment Lifecycle Flow

Diagram ini fokus pada alur payment generik lintas driver melalui API manager/facade PayID.

```mermaid
sequenceDiagram
    participant App as Application
    participant Manager as PayIdManager
    participant Driver as Selected Driver
    participant Provider as Payment Provider
    participant Events as Event Dispatcher
    participant Ledger as Optional Ledger

    App->>Manager: charge(ChargeRequest)
    Manager->>Manager: resolveDriver and supports(charge)
    Manager->>Driver: charge(request)
    Driver->>Provider: create payment
    Provider-->>Driver: payment response
    Driver-->>Manager: ChargeResponse normalized
    Manager->>Ledger: recordChargeAttempt optional
    Manager->>Events: PaymentCharged
    Manager-->>App: ChargeResponse

    App->>Manager: status(orderId)
    Manager->>Manager: resolveDriver and supports(status)
    Manager->>Driver: status(orderId)
    Driver->>Provider: get status
    Provider-->>Driver: status response
    Driver-->>Manager: StatusResponse normalized
    Manager->>Ledger: upsert status snapshot optional
    Manager->>Events: PaymentStatusChecked
    Manager-->>App: StatusResponse
```

```mermaid
flowchart TD
    A[App memilih operasi lifecycle] --> B{Capability tersedia?}
    B -->|No| C[UnsupportedCapabilityException]
    B -->|Yes| D[Forward ke driver]

    D --> E[cancel]
    D --> F[expire]
    D --> G[approve]
    D --> H[deny]
    D --> I[refund]

    E --> J[StatusResponse]
    F --> J
    G --> J
    H --> J
    I --> K[RefundResponse]

    J --> L[Dispatch event status related]
    K --> M[Dispatch PaymentRefunded]
```

Catatan:
- Midtrans mendukung seluruh lifecycle di atas.
- Xendit saat ini mendukung flow generik: charge, status, refund.
- iPaymu saat ini mendukung flow generik: charge, status (refund/cancel/expire belum di-expose).
