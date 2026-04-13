<?php

namespace Aliziodev\PayId\DTO;

use Aliziodev\PayId\Enums\SubscriptionInterval;
use Carbon\Carbon;

final class SubscriptionRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        /** ID unik subscription di sisi merchant. */
        public readonly string $subscriptionId,
        /** Nama deskriptif subscription yang terlihat di dashboard provider. */
        public readonly string $name,
        public readonly int $amount,
        public readonly string $currency,
        /** Token / saved payment method ID yang akan dicharge secara recurring. */
        public readonly string $token,
        public readonly SubscriptionInterval $interval,
        /** Berapa kali interval per siklus. Misal: 2 + Month = setiap 2 bulan. */
        public readonly int $intervalCount,
        /** Waktu mulai subscription. Null = segera. */
        public readonly ?Carbon $startTime = null,
        /** Maksimal berapa kali subscription ini boleh dicharge. Null = tanpa batas. */
        public readonly ?int $maxCycle = null,
        public readonly ?CustomerData $customer = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            subscriptionId: $data['subscription_id'],
            name: $data['name'],
            amount: $data['amount'],
            currency: $data['currency'] ?? 'IDR',
            token: $data['token'],
            interval: $data['interval'] instanceof SubscriptionInterval
                ? $data['interval']
                : SubscriptionInterval::from($data['interval']),
            intervalCount: $data['interval_count'] ?? 1,
            startTime: isset($data['start_time']) ? Carbon::parse($data['start_time']) : null,
            maxCycle: $data['max_cycle'] ?? null,
            customer: isset($data['customer'])
                ? ($data['customer'] instanceof CustomerData
                    ? $data['customer']
                    : CustomerData::make($data['customer']))
                : null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
