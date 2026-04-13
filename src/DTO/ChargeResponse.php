<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentStatus;
use Carbon\Carbon;

final class ChargeResponse
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly string $providerName,
        public readonly string $providerTransactionId,
        public readonly string $merchantOrderId,
        public readonly PaymentStatus $status,
        public readonly array $rawResponse,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $qrString = null,
        public readonly ?string $vaNumber = null,
        public readonly ?string $vaBankCode = null,
        public readonly ?Carbon $expiresAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            providerName: $data['provider_name'],
            providerTransactionId: $data['provider_transaction_id'],
            merchantOrderId: $data['merchant_order_id'],
            status: $data['status'] instanceof PaymentStatus
                ? $data['status']
                : PaymentStatus::from($data['status']),
            rawResponse: $data['raw_response'] ?? [],
            paymentUrl: $data['payment_url'] ?? null,
            qrString: $data['qr_string'] ?? null,
            vaNumber: $data['va_number'] ?? null,
            vaBankCode: $data['va_bank_code'] ?? null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
        );
    }
}
