<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\SubscriptionInterval;

final class UpdateSubscriptionRequest
{
    public function __construct(
        public readonly string $providerSubscriptionId,
        public readonly ?string $name = null,
        public readonly ?int $amount = null,
        public readonly ?string $token = null,
        public readonly ?SubscriptionInterval $interval = null,
        public readonly ?int $intervalCount = null,
        public readonly ?int $maxCycle = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            providerSubscriptionId: $data['provider_subscription_id'],
            name: $data['name'] ?? null,
            amount: $data['amount'] ?? null,
            token: $data['token'] ?? null,
            interval: isset($data['interval'])
                ? ($data['interval'] instanceof SubscriptionInterval
                    ? $data['interval']
                    : SubscriptionInterval::from($data['interval']))
                : null,
            intervalCount: $data['interval_count'] ?? null,
            maxCycle: $data['max_cycle'] ?? null,
        );
    }
}
