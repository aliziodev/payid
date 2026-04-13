<?php

namespace Aliziodev\PayId\Managers;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Contracts\SupportsApprove;
use Aliziodev\PayId\Contracts\SupportsCancel;
use Aliziodev\PayId\Contracts\SupportsCharge;
use Aliziodev\PayId\Contracts\SupportsDeny;
use Aliziodev\PayId\Contracts\SupportsDirectCharge;
use Aliziodev\PayId\Contracts\SupportsExpire;
use Aliziodev\PayId\Contracts\SupportsRefund;
use Aliziodev\PayId\Contracts\SupportsStatus;
use Aliziodev\PayId\Contracts\SupportsSubscription;
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\RefundRequest;
use Aliziodev\PayId\DTO\RefundResponse;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\DTO\SubscriptionRequest;
use Aliziodev\PayId\DTO\SubscriptionResponse;
use Aliziodev\PayId\DTO\UpdateSubscriptionRequest;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Events\PaymentApproved;
use Aliziodev\PayId\Events\PaymentCancelled;
use Aliziodev\PayId\Events\PaymentCharged;
use Aliziodev\PayId\Events\PaymentDenied;
use Aliziodev\PayId\Events\PaymentExpired;
use Aliziodev\PayId\Events\PaymentRefunded;
use Aliziodev\PayId\Events\PaymentStatusChecked;
use Aliziodev\PayId\Events\SubscriptionCancelled;
use Aliziodev\PayId\Events\SubscriptionCreated;
use Aliziodev\PayId\Events\SubscriptionPaused;
use Aliziodev\PayId\Events\SubscriptionResumed;
use Aliziodev\PayId\Events\SubscriptionUpdated;
use Aliziodev\PayId\Exceptions\MissingDriverConfigException;
use Aliziodev\PayId\Exceptions\UnsupportedCapabilityException;
use Aliziodev\PayId\Factories\DriverFactory;
use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;

class PayIdManager
{
    /** @var array<string, DriverInterface> */
    protected array $resolvedDrivers = [];

    protected ?string $activeDriver = null;

    /** @var (Closure(string): array<string, mixed>)|null */
    protected ?Closure $credentialResolver = null;

    public function __construct(
        protected readonly Config $config,
        protected readonly DriverFactory $factory,
        protected readonly Dispatcher $events,
    ) {}

    /**
     * Pilih driver yang akan digunakan untuk request ini.
     * Mengembalikan instance baru agar tidak mengubah state manager asli.
     */
    public function driver(string $name): static
    {
        $clone = clone $this;
        $clone->activeDriver = $name;

        return $clone;
    }

    /**
     * Daftarkan custom resolver untuk driver tertentu.
     *
     * @param  callable(array<string, mixed>): DriverInterface  $resolver
     */
    public function extend(string $driver, callable $resolver): void
    {
        $this->factory->extend($driver, $resolver);
    }

    /**
     * Daftarkan credential resolver untuk keperluan multi-tenant.
     *
     * Signature: function(string $driver): array<string, mixed>
     *
     * @param  callable(string): array<string, mixed>  $resolver
     */
    public function resolveCredentialsUsing(callable $resolver): void
    {
        $this->credentialResolver = Closure::fromCallable($resolver);
    }

    // -----------------------------------------------------------------------
    // Payment operations
    // -----------------------------------------------------------------------

    public function charge(ChargeRequest $request): ChargeResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Charge);

        /** @var DriverInterface&SupportsCharge $driver */
        $response = $driver->charge($request);

        $this->events->dispatch(new PaymentCharged($response));

        return $response;
    }

    public function directCharge(ChargeRequest $request): ChargeResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::DirectCharge);

        /** @var DriverInterface&SupportsDirectCharge $driver */
        $response = $driver->directCharge($request);

        $this->events->dispatch(new PaymentCharged($response));

        return $response;
    }

    public function status(string $merchantOrderId): StatusResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Status);

        /** @var DriverInterface&SupportsStatus $driver */
        $response = $driver->status($merchantOrderId);

        $this->events->dispatch(new PaymentStatusChecked($response));

        return $response;
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Refund);

        /** @var DriverInterface&SupportsRefund $driver */
        $response = $driver->refund($request);

        $this->events->dispatch(new PaymentRefunded($response));

        return $response;
    }

    public function cancel(string $merchantOrderId): StatusResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Cancel);

        /** @var DriverInterface&SupportsCancel $driver */
        $response = $driver->cancel($merchantOrderId);

        $this->events->dispatch(new PaymentCancelled($response));

        return $response;
    }

    public function expire(string $merchantOrderId): StatusResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Expire);

        /** @var DriverInterface&SupportsExpire $driver */
        $response = $driver->expire($merchantOrderId);

        $this->events->dispatch(new PaymentExpired($response));

        return $response;
    }

    public function approve(string $merchantOrderId): StatusResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Approve);

        /** @var DriverInterface&SupportsApprove $driver */
        $response = $driver->approve($merchantOrderId);

        $this->events->dispatch(new PaymentApproved($response));

        return $response;
    }

    public function deny(string $merchantOrderId): StatusResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::Deny);

        /** @var DriverInterface&SupportsDeny $driver */
        $response = $driver->deny($merchantOrderId);

        $this->events->dispatch(new PaymentDenied($response));

        return $response;
    }

    // -----------------------------------------------------------------------
    // Subscription
    // -----------------------------------------------------------------------

    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::CreateSubscription);

        /** @var DriverInterface&SupportsSubscription $driver */
        $response = $driver->createSubscription($request);

        $this->events->dispatch(new SubscriptionCreated($response));

        return $response;
    }

    public function getSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::GetSubscription);

        /** @var DriverInterface&SupportsSubscription $driver */
        return $driver->getSubscription($providerSubscriptionId);
    }

    public function updateSubscription(UpdateSubscriptionRequest $request): SubscriptionResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::UpdateSubscription);

        /** @var DriverInterface&SupportsSubscription $driver */
        $response = $driver->updateSubscription($request);

        $this->events->dispatch(new SubscriptionUpdated($response));

        return $response;
    }

    public function pauseSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::PauseSubscription);

        /** @var DriverInterface&SupportsSubscription $driver */
        $response = $driver->pauseSubscription($providerSubscriptionId);

        $this->events->dispatch(new SubscriptionPaused($response));

        return $response;
    }

    public function resumeSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::ResumeSubscription);

        /** @var DriverInterface&SupportsSubscription $driver */
        $response = $driver->resumeSubscription($providerSubscriptionId);

        $this->events->dispatch(new SubscriptionResumed($response));

        return $response;
    }

    public function cancelSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $driver = $this->resolveDriver();
        $this->assertSupports($driver, Capability::CancelSubscription);

        /** @var DriverInterface&SupportsSubscription $driver */
        $response = $driver->cancelSubscription($providerSubscriptionId);

        $this->events->dispatch(new SubscriptionCancelled($response));

        return $response;
    }

    // -----------------------------------------------------------------------
    // Utilities
    // -----------------------------------------------------------------------

    public function supports(Capability $capability): bool
    {
        return $this->resolveDriver()->supports($capability);
    }

    public function getDriver(): DriverInterface
    {
        return $this->resolveDriver();
    }

    // -----------------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------------

    protected function resolveDriver(): DriverInterface
    {
        $name = $this->activeDriver ?? $this->config->get('payid.default');

        if (! isset($this->resolvedDrivers[$name])) {
            // Custom resolvers (e.g. fake driver) bypass config requirement
            if ($this->factory->hasResolver($name)) {
                $this->resolvedDrivers[$name] = $this->factory->make($name, []);
            } else {
                $config = $this->resolveDriverConfig($name);
                $this->resolvedDrivers[$name] = $this->factory->make($name, $config);
            }
        }

        return $this->resolvedDrivers[$name];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveDriverConfig(string $name): array
    {
        $config = $this->config->get("payid.drivers.{$name}");

        if (! $config) {
            throw new MissingDriverConfigException($name);
        }

        if ($this->credentialResolver !== null) {
            $resolved = ($this->credentialResolver)($name);
            $config = array_merge($config, $resolved);
        }

        return $config;
    }

    protected function assertSupports(DriverInterface $driver, Capability $capability): void
    {
        if (! $driver->supports($capability)) {
            throw new UnsupportedCapabilityException($driver->getName(), $capability);
        }
    }
}
