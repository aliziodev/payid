<?php

namespace Aliziodev\PayId\DTO;

final class RefundRequest
{
    public function __construct(
        public readonly string $merchantOrderId,
        public readonly int $amount,
        public readonly ?string $reason = null,
        public readonly ?string $refundKey = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            merchantOrderId: $data['merchant_order_id'],
            amount: $data['amount'],
            reason: $data['reason'] ?? null,
            refundKey: $data['refund_key'] ?? null,
        );
    }
}
