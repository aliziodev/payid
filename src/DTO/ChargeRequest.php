<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\PaymentChannel;
use Carbon\Carbon;

final class ChargeRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $merchantOrderId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly PaymentChannel $channel,
        public readonly CustomerData $customer,
        /** @var ItemData[] */
        public readonly array $items = [],
        public readonly ?string $description = null,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $successUrl = null,
        public readonly ?string $failureUrl = null,
        public readonly ?Carbon $expiresAt = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            merchantOrderId: $data['merchant_order_id'],
            amount: $data['amount'],
            currency: $data['currency'] ?? 'IDR',
            channel: $data['channel'] instanceof PaymentChannel
                ? $data['channel']
                : PaymentChannel::from($data['channel']),
            customer: $data['customer'] instanceof CustomerData
                ? $data['customer']
                : CustomerData::make($data['customer']),
            items: array_map(
                static fn ($item) => $item instanceof ItemData ? $item : ItemData::make($item),
                $data['items'] ?? [],
            ),
            description: $data['description'] ?? null,
            callbackUrl: $data['callback_url'] ?? null,
            successUrl: $data['success_url'] ?? null,
            failureUrl: $data['failure_url'] ?? null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
