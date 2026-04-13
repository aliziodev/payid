# Driver Extension Flow

Diagram ini menjelaskan operasi provider-specific yang dipanggil lewat driver asli (`getDriver`).

## Midtrans extension flow

```mermaid
flowchart LR
    A[App] --> B[PayId driver midtrans getDriver]
    B --> C[getSnapBiTransactionStatus]
    B --> D[createPaymentLink getPaymentLink deletePaymentLink]
    B --> E[getBalanceMutation]
    B --> F[createInvoice getInvoice voidInvoice]
    B --> G[linkGopayAccount]

    C --> H[Midtrans Snap-BI APIs]
    D --> I[Midtrans Payment Link APIs]
    E --> J[Midtrans Balance APIs]
    F --> K[Midtrans Invoicing APIs]
    G --> L[Midtrans GoPay Linking APIs]
```

## Xendit extension flow

```mermaid
flowchart LR
    A[App] --> B[PayId driver xendit getDriver]
    B --> C[createPaymentMethod getPaymentMethod]
    B --> D[createPaymentRequest getPaymentRequest]
    B --> E[createCustomer getCustomer]
    B --> F[createPayout getPayout]
    B --> G[getBalance]
    B --> H[getTransaction listTransactions]

    C --> I[Xendit Payment Method APIs]
    D --> J[Xendit Payment Request APIs]
    E --> K[Xendit Customer APIs]
    F --> L[Xendit Payout APIs]
    G --> M[Xendit Balance APIs]
    H --> N[Xendit Transaction APIs]
```

Prinsip:
- Gunakan extension method hanya untuk fitur provider-specific.
- Untuk flow lintas driver, tetap prioritaskan API manager/facade PayID.

## iPaymu extension flow

```mermaid
flowchart LR
    A[App] --> B[PayId driver ipaymu getDriver]
    B --> C[directPayment]
    B --> D[paymentChannels]
    B --> E[checkBalance]
    B --> F[historyTransaction]

    C --> G[iPaymu Direct Payment API]
    D --> H[iPaymu Payment Channel API]
    E --> I[iPaymu Balance API]
    F --> J[iPaymu History Transaction API]
```
