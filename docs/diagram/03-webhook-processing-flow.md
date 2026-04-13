# Webhook Processing Flow

Diagram ini fokus pada inbound webhook route sampai event dispatch di PayID.

```mermaid
flowchart TD
    A[Provider kirim webhook] --> B[POST configured webhook endpoint]
    B --> C[WebhookController invoke]
    C --> D[WebhookProcessor handle]

    D --> E[recordWebhookEvent optional]
    D --> F[resolve driver by route parameter]

    F --> G{Driver supports verification?}
    G -->|No| J
    G -->|Yes| H[verifyWebhook]
    H --> I{verification valid?}
    I -->|No| I1[dispatch WebhookVerificationFailed]
    I1 --> I2[mark processed false optional]
    I2 --> I3[return 401]

    I -->|Yes| J[continue]

    J --> K{Driver supports parsing?}
    K -->|No| K1[mark processed false optional]
    K1 --> K2[return 422]
    K -->|Yes| L[parseWebhook]

    L --> M{parse success?}
    M -->|No| M1[dispatch WebhookParsingFailed]
    M1 --> M2[mark processed false optional]
    M2 --> M3[return 422]

    M -->|Yes| N[upsertStatusFromWebhook optional]
    N --> O[dispatch WebhookReceived]
    O --> P[mark processed true optional]
    P --> Q[return 200]
```

Event utama:
- WebhookReceived
- WebhookVerificationFailed
- WebhookParsingFailed

Catatan iPaymu:
- Verification bisa diaktifkan strict melalui `IPAYMU_WEBHOOK_VERIFY=true`.
- Jika disabled, verifier iPaymu mengembalikan true agar webhook tetap diproses.
