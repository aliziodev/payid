<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Carbon\Carbon;

final class NormalizedWebhook
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $merchantOrderId,
        public readonly PaymentStatus $status,
        public readonly bool $signatureValid,
        public readonly array $rawPayload,
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $eventType = null,
        public readonly ?int $amount = null,
        public readonly ?string $currency = null,
        public readonly ?PaymentChannel $channel = null,
        public readonly ?Carbon $occurredAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            provider: $data['provider'],
            merchantOrderId: $data['merchant_order_id'],
            status: $data['status'] instanceof PaymentStatus
                ? $data['status']
                : PaymentStatus::from($data['status']),
            signatureValid: $data['signature_valid'] ?? false,
            rawPayload: $data['raw_payload'] ?? [],
            providerTransactionId: $data['provider_transaction_id'] ?? null,
            eventType: $data['event_type'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            channel: isset($data['channel'])
                ? ($data['channel'] instanceof PaymentChannel
                    ? $data['channel']
                    : PaymentChannel::from($data['channel']))
                : null,
            occurredAt: isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : null,
        );
    }
}
