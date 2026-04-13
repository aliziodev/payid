<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentStatus;
use Carbon\Carbon;

final class RefundResponse
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly string $providerName,
        public readonly string $merchantOrderId,
        public readonly string $refundId,
        public readonly PaymentStatus $status,
        public readonly array $rawResponse,
        public readonly ?int $amount = null,
        public readonly ?Carbon $refundedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            providerName: $data['provider_name'],
            merchantOrderId: $data['merchant_order_id'],
            refundId: $data['refund_id'],
            status: $data['status'] instanceof PaymentStatus
                ? $data['status']
                : PaymentStatus::from($data['status']),
            rawResponse: $data['raw_response'] ?? [],
            amount: $data['amount'] ?? null,
            refundedAt: isset($data['refunded_at']) ? Carbon::parse($data['refunded_at']) : null,
        );
    }
}
