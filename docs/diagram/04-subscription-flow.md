# Subscription Flow

Diagram ini merangkum alur subscription operation pada PayID manager.

```mermaid
flowchart TD
    A[App panggil API subscription] --> B{Jenis operasi}

    B --> C[createSubscription]
    B --> D[getSubscription]
    B --> E[updateSubscription]
    B --> F[pauseSubscription]
    B --> G[resumeSubscription]
    B --> H[cancelSubscription]

    C --> I[assert capability create_subscription]
    D --> J[assert capability get_subscription]
    E --> K[assert capability update_subscription]
    F --> L[assert capability pause_subscription]
    G --> M[assert capability resume_subscription]
    H --> N[assert capability cancel_subscription]

    I --> O[forward ke driver]
    J --> O
    K --> O
    L --> O
    M --> O
    N --> O

    O --> P[driver call provider API]
    P --> Q[normalize ke SubscriptionResponse]

    Q --> R[dispatch event untuk create update pause resume cancel]
    Q --> S[return response ke app]
```

Catatan:
- Midtrans: seluruh subscription operation tersedia.
- Xendit: belum expose subscription operation di driver saat ini.
