<?php

namespace Aliziodev\PayId\Testing;

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\DTO\RefundRequest;
use Aliziodev\PayId\DTO\RefundResponse;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\DTO\SubscriptionResponse;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Managers\PayIdManager;
use Illuminate\Contracts\Config\Repository as Config;
use PHPUnit\Framework\Assert;

class PayIdFake
{
    protected FakeDriver $fakeDriver;

    public function __construct(
        protected readonly PayIdManager $manager,
        protected readonly Config $config,
    ) {
        $this->fakeDriver = new FakeDriver;

        // Daftarkan 'fake' driver resolver
        $this->manager->extend('fake', fn () => $this->fakeDriver);

        // Bypass credential validation untuk fake driver
        $this->manager->resolveCredentialsUsing(fn () => []);

        // Set default driver ke 'fake' sehingga semua panggilan tanpa driver() eksplisit menggunakan fake
        $this->config->set('payid.default', 'fake');
    }

    // -----------------------------------------------------------------------
    // Setup: queue responses
    // -----------------------------------------------------------------------

    public function fakeCharge(ChargeResponse $response): void
    {
        $this->fakeDriver->queueCharge($response);
    }

    public function fakeDirectCharge(ChargeResponse $response): void
    {
        $this->fakeDriver->queueDirectCharge($response);
    }

    public function fakeStatus(StatusResponse $response): void
    {
        $this->fakeDriver->queueStatus($response);
    }

    public function fakeRefund(RefundResponse $response): void
    {
        $this->fakeDriver->queueRefund($response);
    }

    public function fakeCancel(StatusResponse $response): void
    {
        $this->fakeDriver->queueCancel($response);
    }

    public function fakeExpire(StatusResponse $response): void
    {
        $this->fakeDriver->queueExpire($response);
    }

    public function fakeApprove(StatusResponse $response): void
    {
        $this->fakeDriver->queueApprove($response);
    }

    public function fakeDeny(StatusResponse $response): void
    {
        $this->fakeDriver->queueDeny($response);
    }

    public function fakeSubscription(SubscriptionResponse $response): void
    {
        $this->fakeDriver->queueSubscription($response);
    }

    public function fakeWebhookVerification(bool $valid): void
    {
        $this->fakeDriver->setWebhookValid($valid);
    }

    public function fakeWebhookResponse(NormalizedWebhook $webhook): void
    {
        $this->fakeDriver->setWebhookResponse($webhook);
    }

    // -----------------------------------------------------------------------
    // Assertions — charge
    // -----------------------------------------------------------------------

    public function assertCharged(int $times = 1): void
    {
        $count = count($this->fakeDriver->getRecordedCharges());
        Assert::assertSame($times, $count, "Expected {$times} charge(s), but {$count} were recorded.");
    }

    public function assertNothingCharged(): void
    {
        Assert::assertEmpty($this->fakeDriver->getRecordedCharges(), 'Expected no charges, but some were recorded.');
    }

    public function assertChargedWith(callable $callback): void
    {
        foreach ($this->fakeDriver->getRecordedCharges() as $charge) {
            if ($callback($charge) === true) {
                return;
            }
        }
        Assert::fail('No charge matching the given criteria was recorded.');
    }

    public function assertDirectCharged(int $times = 1): void
    {
        $count = count($this->fakeDriver->getRecordedDirectCharges());
        Assert::assertSame($times, $count, "Expected {$times} directCharge(s), but {$count} were recorded.");
    }

    public function assertNothingDirectCharged(): void
    {
        Assert::assertEmpty($this->fakeDriver->getRecordedDirectCharges(), 'Expected no direct charges, but some were recorded.');
    }

    public function assertDirectChargedWith(callable $callback): void
    {
        foreach ($this->fakeDriver->getRecordedDirectCharges() as $charge) {
            if ($callback($charge) === true) {
                return;
            }
        }
        Assert::fail('No direct charge matching the given criteria was recorded.');
    }

    // -----------------------------------------------------------------------
    // Assertions — status
    // -----------------------------------------------------------------------

    public function assertStatusChecked(string $merchantOrderId): void
    {
        Assert::assertContains(
            $merchantOrderId,
            $this->fakeDriver->getRecordedStatuses(),
            "Status for order [{$merchantOrderId}] was never checked.",
        );
    }

    public function assertStatusCheckedTimes(int $times): void
    {
        $count = count($this->fakeDriver->getRecordedStatuses());
        Assert::assertSame($times, $count, "Expected {$times} status check(s), but {$count} were recorded.");
    }

    // -----------------------------------------------------------------------
    // Assertions — refund / cancel / expire
    // -----------------------------------------------------------------------

    public function assertRefunded(int $times = 1): void
    {
        $count = count($this->fakeDriver->getRecordedRefunds());
        Assert::assertSame($times, $count, "Expected {$times} refund(s), but {$count} were recorded.");
    }

    public function assertRefundedWith(callable $callback): void
    {
        foreach ($this->fakeDriver->getRecordedRefunds() as $refund) {
            if ($callback($refund) === true) {
                return;
            }
        }
        Assert::fail('No refund matching the given criteria was recorded.');
    }

    public function assertCancelled(int $times = 1): void
    {
        $count = count($this->fakeDriver->getRecordedCancels());
        Assert::assertSame($times, $count, "Expected {$times} cancel(s), but {$count} were recorded.");
    }

    public function assertExpired(int $times = 1): void
    {
        $count = count($this->fakeDriver->getRecordedExpires());
        Assert::assertSame($times, $count, "Expected {$times} expire(s), but {$count} were recorded.");
    }

    // -----------------------------------------------------------------------
    // Assertions — approve / deny
    // -----------------------------------------------------------------------

    public function assertApproved(int $times = 1): void
    {
        $count = count($this->fakeDriver->getRecordedApprovals());
        Assert::assertSame($times, $count, "Expected {$times} approval(s), but {$count} were recorded.");
    }

    public function assertApproved_forOrder(string $merchantOrderId): void
    {
        Assert::assertContains(
            $merchantOrderId,
            $this->fakeDriver->getRecordedApprovals(),
            "Approval for order [{$merchantOrderId}] was never recorded.",
        );
    }

    public function assertDenied(int $times = 1): void
    {
        $count = count($this->fakeDriver->getRecordedDenials());
        Assert::assertSame($times, $count, "Expected {$times} denial(s), but {$count} were recorded.");
    }

    // -----------------------------------------------------------------------
    // Assertions — subscription
    // -----------------------------------------------------------------------

    public function assertSubscriptionCreated(int $times = 1): void
    {
        $creates = array_filter(
            $this->fakeDriver->getRecordedSubscriptions(),
            fn ($r) => $r['action'] === 'create',
        );
        $count = count($creates);
        Assert::assertSame($times, $count, "Expected {$times} subscription creation(s), but {$count} were recorded.");
    }

    public function assertSubscriptionCreatedWith(callable $callback): void
    {
        foreach ($this->fakeDriver->getRecordedSubscriptions() as $record) {
            if ($record['action'] === 'create' && $callback($record['request']) === true) {
                return;
            }
        }
        Assert::fail('No subscription creation matching the given criteria was recorded.');
    }

    public function assertSubscriptionCancelled(string $providerSubscriptionId): void
    {
        $cancels = array_filter(
            $this->fakeDriver->getRecordedSubscriptions(),
            fn ($r) => $r['action'] === 'cancel' && ($r['id'] ?? null) === $providerSubscriptionId,
        );
        Assert::assertNotEmpty($cancels, "Cancellation for subscription [{$providerSubscriptionId}] was never recorded.");
    }

    // -----------------------------------------------------------------------
    // Global assertion
    // -----------------------------------------------------------------------

    public function assertNothingRecorded(): void
    {
        Assert::assertEmpty($this->fakeDriver->getRecordedCharges(), 'Unexpected charges were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedDirectCharges(), 'Unexpected direct charges were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedStatuses(), 'Unexpected status checks were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedRefunds(), 'Unexpected refunds were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedCancels(), 'Unexpected cancels were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedExpires(), 'Unexpected expires were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedApprovals(), 'Unexpected approvals were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedDenials(), 'Unexpected denials were recorded.');
        Assert::assertEmpty($this->fakeDriver->getRecordedSubscriptions(), 'Unexpected subscription calls were recorded.');
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /** @return array<int, ChargeRequest> */
    public function getRecordedCharges(): array
    {
        return $this->fakeDriver->getRecordedCharges();
    }

    /** @return array<int, ChargeRequest> */
    public function getRecordedDirectCharges(): array
    {
        return $this->fakeDriver->getRecordedDirectCharges();
    }

    /** @return array<int, string> */
    public function getRecordedStatuses(): array
    {
        return $this->fakeDriver->getRecordedStatuses();
    }

    /** @return array<int, RefundRequest> */
    public function getRecordedRefunds(): array
    {
        return $this->fakeDriver->getRecordedRefunds();
    }

    public function getFakeDriver(): FakeDriver
    {
        return $this->fakeDriver;
    }

    public function supports(Capability $capability): bool
    {
        return $this->fakeDriver->supports($capability);
    }
}
