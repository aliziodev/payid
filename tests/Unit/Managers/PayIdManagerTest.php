<?php

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\RefundRequest;
use Aliziodev\PayId\DTO\SubscriptionRequest;
use Aliziodev\PayId\DTO\UpdateSubscriptionRequest;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\SubscriptionInterval;
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
use Aliziodev\PayId\Managers\PayIdManager;
use Aliziodev\PayId\Testing\FakeDriver;
use Illuminate\Support\Facades\Event;

describe('PayIdManager', function () {

    beforeEach(function () {
        // config('payid.default') sudah di-set ke 'fake' di TestCase
        // config('payid.drivers.fake') sudah di-set ke ['driver' => 'fake']
        app('payid')->extend('fake', fn () => new FakeDriver);
    });

    it('can resolve the default driver', function () {
        $manager = app(PayIdManager::class);
        $driver = $manager->getDriver();

        expect($driver)->toBeInstanceOf(FakeDriver::class);
        expect($driver->getName())->toBe('fake');
    });

    it('can switch driver at runtime', function () {
        $manager = app(PayIdManager::class);

        app('config')->set('payid.drivers.fake2', ['driver' => 'fake2']);
        app('payid')->extend('fake2', fn () => new FakeDriver);

        $driver = $manager->driver('fake2')->getDriver();
        expect($driver)->toBeInstanceOf(FakeDriver::class);
    });

    it('throws MissingDriverConfigException for unconfigured driver', function () {
        $manager = app(PayIdManager::class);

        expect(fn () => $manager->driver('nonexistent')->getDriver())
            ->toThrow(MissingDriverConfigException::class);
    });

    it('dispatches PaymentCharged event after charge', function () {
        $charged = false;

        Event::listen(
            PaymentCharged::class,
            function () use (&$charged) {
                $charged = true;
            },
        );

        $manager = app(PayIdManager::class);
        $manager->charge(ChargeRequest::make([
            'merchant_order_id' => 'ORDER-001',
            'amount' => 100000,
            'channel' => PaymentChannel::Qris,
            'customer' => ['name' => 'Budi', 'email' => 'budi@example.com'],
        ]));

        expect($charged)->toBeTrue();
    });

    it('dispatches PaymentStatusChecked event after status check', function () {
        $checked = false;

        Event::listen(
            PaymentStatusChecked::class,
            function () use (&$checked) {
                $checked = true;
            },
        );

        app(PayIdManager::class)->status('ORDER-001');

        expect($checked)->toBeTrue();
    });

    it('dispatches PaymentCharged event after directCharge', function () {
        $charged = false;

        Event::listen(
            PaymentCharged::class,
            function () use (&$charged) {
                $charged = true;
            },
        );

        $manager = app(PayIdManager::class);

        $manager->directCharge(ChargeRequest::make([
            'merchant_order_id' => 'ORDER-DIRECT-001',
            'amount' => 120000,
            'channel' => PaymentChannel::Qris,
            'customer' => ['name' => 'Dina', 'email' => 'dina@example.com'],
        ]));

        expect($charged)->toBeTrue();
    });

    it('dispatches payment lifecycle events for refund/cancel/expire/approve/deny', function () {
        $refunded = false;
        $cancelled = false;
        $expired = false;
        $approved = false;
        $denied = false;

        Event::listen(PaymentRefunded::class, function () use (&$refunded) {
            $refunded = true;
        });
        Event::listen(PaymentCancelled::class, function () use (&$cancelled) {
            $cancelled = true;
        });
        Event::listen(PaymentExpired::class, function () use (&$expired) {
            $expired = true;
        });
        Event::listen(PaymentApproved::class, function () use (&$approved) {
            $approved = true;
        });
        Event::listen(PaymentDenied::class, function () use (&$denied) {
            $denied = true;
        });

        $manager = app(PayIdManager::class);

        $manager->refund(RefundRequest::make([
            'merchant_order_id' => 'ORDER-REFUND-001',
            'amount' => 50000,
        ]));

        $manager->cancel('ORDER-CANCEL-001');
        $manager->expire('ORDER-EXPIRE-001');
        $manager->approve('ORDER-APPROVE-001');
        $manager->deny('ORDER-DENY-001');

        expect($refunded)->toBeTrue();
        expect($cancelled)->toBeTrue();
        expect($expired)->toBeTrue();
        expect($approved)->toBeTrue();
        expect($denied)->toBeTrue();
    });

    it('handles full subscription lifecycle and dispatches related events', function () {
        $createdEvent = false;
        $updatedEvent = false;
        $pausedEvent = false;
        $resumedEvent = false;
        $cancelledEvent = false;

        Event::listen(SubscriptionCreated::class, function () use (&$createdEvent) {
            $createdEvent = true;
        });
        Event::listen(SubscriptionUpdated::class, function () use (&$updatedEvent) {
            $updatedEvent = true;
        });
        Event::listen(SubscriptionPaused::class, function () use (&$pausedEvent) {
            $pausedEvent = true;
        });
        Event::listen(SubscriptionResumed::class, function () use (&$resumedEvent) {
            $resumedEvent = true;
        });
        Event::listen(SubscriptionCancelled::class, function () use (&$cancelledEvent) {
            $cancelledEvent = true;
        });

        $manager = app(PayIdManager::class);

        $created = $manager->createSubscription(SubscriptionRequest::make([
            'subscription_id' => 'SUB-001',
            'name' => 'Gold Plan',
            'amount' => 100000,
            'token' => 'TOKEN-001',
            'interval' => SubscriptionInterval::Month,
            'interval_count' => 1,
        ]));

        $fetched = $manager->getSubscription($created->providerSubscriptionId);

        $updated = $manager->updateSubscription(UpdateSubscriptionRequest::make([
            'provider_subscription_id' => $fetched->providerSubscriptionId,
            'name' => 'Gold Plan Updated',
            'amount' => 125000,
            'interval' => SubscriptionInterval::Month,
            'interval_count' => 1,
        ]));

        $paused = $manager->pauseSubscription($updated->providerSubscriptionId);
        $resumed = $manager->resumeSubscription($updated->providerSubscriptionId);
        $cancelled = $manager->cancelSubscription($updated->providerSubscriptionId);

        expect($created->providerName)->toBe('fake');
        expect($fetched->providerName)->toBe('fake');
        expect($paused->providerName)->toBe('fake');
        expect($resumed->providerName)->toBe('fake');
        expect($cancelled->providerName)->toBe('fake');

        expect($createdEvent)->toBeTrue();
        expect($updatedEvent)->toBeTrue();
        expect($pausedEvent)->toBeTrue();
        expect($resumedEvent)->toBeTrue();
        expect($cancelledEvent)->toBeTrue();
    });

    it('throws UnsupportedCapabilityException for unsupported capability', function () {
        // Buat driver yang tidak support Refund
        $limitedDriver = new class extends FakeDriver
        {
            public function getCapabilities(): array
            {
                return [Capability::Charge, Capability::Status];
            }
        };

        app('config')->set('payid.drivers.limited', ['driver' => 'limited']);
        app('payid')->extend('limited', fn () => $limitedDriver);

        $manager = app(PayIdManager::class);

        expect(
            fn () => $manager->driver('limited')->refund(
                RefundRequest::make([
                    'merchant_order_id' => 'ORDER-001',
                    'amount' => 50000,
                ]),
            ),
        )->toThrow(UnsupportedCapabilityException::class);
    });

    it('supports capability check', function () {
        $manager = app(PayIdManager::class);

        expect($manager->supports(Capability::Charge))->toBeTrue();
        expect($manager->supports(Capability::Refund))->toBeTrue();
        expect($manager->supports(Capability::WebhookParsing))->toBeTrue();
    });

    it('manager driver() returns new instance (does not mutate original)', function () {
        $manager = app(PayIdManager::class);
        $switched = $manager->driver('fake');

        expect($manager)->not->toBe($switched);
    });

});
