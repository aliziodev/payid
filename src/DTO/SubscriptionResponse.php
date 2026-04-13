<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\SubscriptionInterval;
use Aliziodev\PayId\Enums\SubscriptionStatus;
use Carbon\Carbon;

final class SubscriptionResponse
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly string $providerName,
        /** ID subscription di sisi provider. */
        public readonly string $providerSubscriptionId,
        /** ID subscription di sisi merchant (yang dikirim saat create). */
        public readonly string $subscriptionId,
        public readonly string $name,
        public readonly SubscriptionStatus $status,
        public readonly int $amount,
        public readonly string $currency,
        public readonly SubscriptionInterval $interval,
        public readonly int $intervalCount,
        public readonly array $rawResponse,
        /** Berapa kali sudah dicharge. */
        public readonly ?int $currentCycle = null,
        /** Maksimal siklus yang diijinkan. */
        public readonly ?int $maxCycle = null,
        public readonly ?Carbon $startTime = null,
        public readonly ?Carbon $nextChargeAt = null,
        public readonly ?Carbon $createdAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            providerName: $data['provider_name'],
            providerSubscriptionId: $data['provider_subscription_id'],
            subscriptionId: $data['subscription_id'],
            name: $data['name'],
            status: $data['status'] instanceof SubscriptionStatus
                ? $data['status']
                : SubscriptionStatus::from($data['status']),
            amount: $data['amount'],
            currency: $data['currency'] ?? 'IDR',
            interval: $data['interval'] instanceof SubscriptionInterval
                ? $data['interval']
                : SubscriptionInterval::from($data['interval']),
            intervalCount: $data['interval_count'] ?? 1,
            rawResponse: $data['raw_response'] ?? [],
            currentCycle: $data['current_cycle'] ?? null,
            maxCycle: $data['max_cycle'] ?? null,
            startTime: isset($data['start_time']) ? Carbon::parse($data['start_time']) : null,
            nextChargeAt: isset($data['next_charge_at']) ? Carbon::parse($data['next_charge_at']) : null,
            createdAt: isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
        );
    }
}
