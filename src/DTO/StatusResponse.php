<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Carbon\Carbon;

final class StatusResponse
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
        public readonly ?Carbon $paidAt = null,
        public readonly ?int $amount = null,
        public readonly ?string $currency = null,
        public readonly ?PaymentChannel $channel = null,
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
            paidAt: isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            channel: isset($data['channel'])
                ? ($data['channel'] instanceof PaymentChannel
                    ? $data['channel']
                    : PaymentChannel::from($data['channel']))
                : null,
        );
    }
}
