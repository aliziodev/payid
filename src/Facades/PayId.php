<?php

namespace Aliziodev\PayId\Facades;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\RefundRequest;
use Aliziodev\PayId\DTO\RefundResponse;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\DTO\SubscriptionRequest;
use Aliziodev\PayId\DTO\SubscriptionResponse;
use Aliziodev\PayId\DTO\UpdateSubscriptionRequest;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Managers\PayIdManager;
use Aliziodev\PayId\Testing\PayIdFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PayIdManager driver(string $name)
 * @method static DriverInterface getDriver()
 * @method static bool supports(Capability $capability)
 * @method static void extend(string $driver, callable $resolver)
 * @method static void resolveCredentialsUsing(callable $resolver)
 *
 * Payment operations:
 * @method static ChargeResponse charge(ChargeRequest $request)
 * @method static ChargeResponse directCharge(ChargeRequest $request)
 * @method static StatusResponse status(string $merchantOrderId)
 * @method static RefundResponse refund(RefundRequest $request)
 * @method static StatusResponse cancel(string $merchantOrderId)
 * @method static StatusResponse expire(string $merchantOrderId)
 * @method static StatusResponse approve(string $merchantOrderId)
 * @method static StatusResponse deny(string $merchantOrderId)
 *
 * Subscription operations:
 * @method static SubscriptionResponse createSubscription(SubscriptionRequest $request)
 * @method static SubscriptionResponse getSubscription(string $providerSubscriptionId)
 * @method static SubscriptionResponse updateSubscription(UpdateSubscriptionRequest $request)
 * @method static SubscriptionResponse pauseSubscription(string $providerSubscriptionId)
 * @method static SubscriptionResponse resumeSubscription(string $providerSubscriptionId)
 * @method static SubscriptionResponse cancelSubscription(string $providerSubscriptionId)
 *
 * @see PayIdManager
 */
class PayId extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayIdManager::class;
    }

    /**
     * Aktifkan fake driver untuk testing.
     * Mengganti default driver ke 'fake' secara otomatis.
     * Kembalikan instance PayIdFake untuk setup dan assertion.
     */
    public static function fake(): PayIdFake
    {
        $app = static::getFacadeApplication();

        return new PayIdFake(
            manager: static::getFacadeRoot(),
            config: $app['config'],
        );
    }
}
