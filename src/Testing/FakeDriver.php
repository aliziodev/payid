<?php

namespace Aliziodev\PayId\Testing;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Contracts\HasCapabilities;
use Aliziodev\PayId\Contracts\SupportsApprove;
use Aliziodev\PayId\Contracts\SupportsCancel;
use Aliziodev\PayId\Contracts\SupportsCharge;
use Aliziodev\PayId\Contracts\SupportsDeny;
use Aliziodev\PayId\Contracts\SupportsDirectCharge;
use Aliziodev\PayId\Contracts\SupportsExpire;
use Aliziodev\PayId\Contracts\SupportsRefund;
use Aliziodev\PayId\Contracts\SupportsStatus;
use Aliziodev\PayId\Contracts\SupportsSubscription;
use Aliziodev\PayId\Contracts\SupportsWebhookParsing;
use Aliziodev\PayId\Contracts\SupportsWebhookVerification;
use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\DTO\RefundRequest;
use Aliziodev\PayId\DTO\RefundResponse;
use Aliziodev\PayId\DTO\StatusResponse;
use Aliziodev\PayId\DTO\SubscriptionRequest;
use Aliziodev\PayId\DTO\SubscriptionResponse;
use Aliziodev\PayId\DTO\UpdateSubscriptionRequest;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayId\Enums\SubscriptionInterval;
use Aliziodev\PayId\Enums\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FakeDriver implements DriverInterface, SupportsApprove, SupportsCancel, SupportsCharge, SupportsDeny, SupportsDirectCharge, SupportsExpire, SupportsRefund, SupportsStatus, SupportsSubscription, SupportsWebhookParsing, SupportsWebhookVerification
{
    use HasCapabilities;

    // Response queues
    /** @var array<int, ChargeResponse> */
    protected array $chargeQueue = [];

    /** @var array<int, ChargeResponse> */
    protected array $directChargeQueue = [];

    /** @var array<int, StatusResponse> */
    protected array $statusQueue = [];

    /** @var array<int, RefundResponse> */
    protected array $refundQueue = [];

    /** @var array<int, StatusResponse> */
    protected array $cancelQueue = [];

    /** @var array<int, StatusResponse> */
    protected array $expireQueue = [];

    /** @var array<int, StatusResponse> */
    protected array $approveQueue = [];

    /** @var array<int, StatusResponse> */
    protected array $denyQueue = [];

    /** @var array<int, SubscriptionResponse> */
    protected array $subscriptionQueue = [];

    protected bool $webhookValid = true;

    protected ?NormalizedWebhook $webhookResponse = null;

    // Recorded calls — populated automatically on each method call
    /** @var array<int, ChargeRequest> */
    protected array $recordedCharges = [];

    /** @var array<int, ChargeRequest> */
    protected array $recordedDirectCharges = [];

    /** @var array<int, string> */
    protected array $recordedStatuses = [];

    /** @var array<int, RefundRequest> */
    protected array $recordedRefunds = [];

    /** @var array<int, string> */
    protected array $recordedCancels = [];

    /** @var array<int, string> */
    protected array $recordedExpires = [];

    /** @var array<int, string> */
    protected array $recordedApprovals = [];

    /** @var array<int, string> */
    protected array $recordedDenials = [];

    /** @var array<int, array<string, mixed>> */
    protected array $recordedSubscriptions = [];

    public function getName(): string
    {
        return 'fake';
    }

    public function getCapabilities(): array
    {
        return [
            Capability::Charge,
            Capability::DirectCharge,
            Capability::Status,
            Capability::Refund,
            Capability::Cancel,
            Capability::Expire,
            Capability::Approve,
            Capability::Deny,
            Capability::CreateSubscription,
            Capability::GetSubscription,
            Capability::UpdateSubscription,
            Capability::PauseSubscription,
            Capability::ResumeSubscription,
            Capability::CancelSubscription,
            Capability::WebhookVerification,
            Capability::WebhookParsing,
        ];
    }

    // -----------------------------------------------------------------------
    // Queue setters (used by PayIdFake setup methods)
    // -----------------------------------------------------------------------

    public function queueCharge(ChargeResponse $response): void
    {
        $this->chargeQueue[] = $response;
    }

    public function queueDirectCharge(ChargeResponse $response): void
    {
        $this->directChargeQueue[] = $response;
    }

    public function queueStatus(StatusResponse $response): void
    {
        $this->statusQueue[] = $response;
    }

    public function queueRefund(RefundResponse $response): void
    {
        $this->refundQueue[] = $response;
    }

    public function queueCancel(StatusResponse $response): void
    {
        $this->cancelQueue[] = $response;
    }

    public function queueExpire(StatusResponse $response): void
    {
        $this->expireQueue[] = $response;
    }

    public function queueApprove(StatusResponse $response): void
    {
        $this->approveQueue[] = $response;
    }

    public function queueDeny(StatusResponse $response): void
    {
        $this->denyQueue[] = $response;
    }

    public function queueSubscription(SubscriptionResponse $response): void
    {
        $this->subscriptionQueue[] = $response;
    }

    public function setWebhookValid(bool $valid): void
    {
        $this->webhookValid = $valid;
    }

    public function setWebhookResponse(NormalizedWebhook $webhook): void
    {
        $this->webhookResponse = $webhook;
    }

    // -----------------------------------------------------------------------
    // Recorded call accessors
    // -----------------------------------------------------------------------

    /** @return array<int, ChargeRequest> */
    public function getRecordedCharges(): array
    {
        return $this->recordedCharges;
    }

    /** @return array<int, ChargeRequest> */
    public function getRecordedDirectCharges(): array
    {
        return $this->recordedDirectCharges;
    }

    /** @return array<int, string> */
    public function getRecordedStatuses(): array
    {
        return $this->recordedStatuses;
    }

    /** @return array<int, RefundRequest> */
    public function getRecordedRefunds(): array
    {
        return $this->recordedRefunds;
    }

    /** @return array<int, string> */
    public function getRecordedCancels(): array
    {
        return $this->recordedCancels;
    }

    /** @return array<int, string> */
    public function getRecordedExpires(): array
    {
        return $this->recordedExpires;
    }

    /** @return array<int, string> */
    public function getRecordedApprovals(): array
    {
        return $this->recordedApprovals;
    }

    /** @return array<int, string> */
    public function getRecordedDenials(): array
    {
        return $this->recordedDenials;
    }

    /** @return array<int, array<string, mixed>> */
    public function getRecordedSubscriptions(): array
    {
        return $this->recordedSubscriptions;
    }

    // -----------------------------------------------------------------------
    // Implementations
    // -----------------------------------------------------------------------

    public function charge(ChargeRequest $request): ChargeResponse
    {
        $this->recordedCharges[] = $request;

        if (! empty($this->chargeQueue)) {
            return array_shift($this->chargeQueue);
        }

        return ChargeResponse::make([
            'provider_name' => 'fake',
            'provider_transaction_id' => 'FAKE-TRX-'.strtoupper(uniqid()),
            'merchant_order_id' => $request->merchantOrderId,
            'status' => PaymentStatus::Pending,
            'payment_url' => 'https://fake.payid.test/pay/'.$request->merchantOrderId,
            'raw_response' => [],
        ]);
    }

    public function directCharge(ChargeRequest $request): ChargeResponse
    {
        $this->recordedDirectCharges[] = $request;

        if (! empty($this->directChargeQueue)) {
            return array_shift($this->directChargeQueue);
        }

        return ChargeResponse::make([
            'provider_name' => 'fake',
            'provider_transaction_id' => 'FAKE-TRX-'.strtoupper(uniqid()),
            'merchant_order_id' => $request->merchantOrderId,
            'status' => PaymentStatus::Pending,
            'va_number' => '1234567890',
            'va_bank_code' => 'BCA',
            'raw_response' => [],
        ]);
    }

    public function status(string $merchantOrderId): StatusResponse
    {
        $this->recordedStatuses[] = $merchantOrderId;

        if (! empty($this->statusQueue)) {
            return array_shift($this->statusQueue);
        }

        return StatusResponse::make([
            'provider_name' => 'fake',
            'provider_transaction_id' => 'FAKE-TRX-'.strtoupper(uniqid()),
            'merchant_order_id' => $merchantOrderId,
            'status' => PaymentStatus::Pending,
            'raw_response' => [],
        ]);
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $this->recordedRefunds[] = $request;

        if (! empty($this->refundQueue)) {
            return array_shift($this->refundQueue);
        }

        return RefundResponse::make([
            'provider_name' => 'fake',
            'merchant_order_id' => $request->merchantOrderId,
            'refund_id' => 'FAKE-REFUND-'.strtoupper(uniqid()),
            'status' => PaymentStatus::Refunded,
            'amount' => $request->amount,
            'refunded_at' => Carbon::now()->toIso8601String(),
            'raw_response' => [],
        ]);
    }

    public function cancel(string $merchantOrderId): StatusResponse
    {
        $this->recordedCancels[] = $merchantOrderId;

        if (! empty($this->cancelQueue)) {
            return array_shift($this->cancelQueue);
        }

        return $this->makeStatusResponse($merchantOrderId, PaymentStatus::Cancelled);
    }

    public function expire(string $merchantOrderId): StatusResponse
    {
        $this->recordedExpires[] = $merchantOrderId;

        if (! empty($this->expireQueue)) {
            return array_shift($this->expireQueue);
        }

        return $this->makeStatusResponse($merchantOrderId, PaymentStatus::Expired);
    }

    public function approve(string $merchantOrderId): StatusResponse
    {
        $this->recordedApprovals[] = $merchantOrderId;

        if (! empty($this->approveQueue)) {
            return array_shift($this->approveQueue);
        }

        return $this->makeStatusResponse($merchantOrderId, PaymentStatus::Authorized);
    }

    public function deny(string $merchantOrderId): StatusResponse
    {
        $this->recordedDenials[] = $merchantOrderId;

        if (! empty($this->denyQueue)) {
            return array_shift($this->denyQueue);
        }

        return $this->makeStatusResponse($merchantOrderId, PaymentStatus::Failed);
    }

    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse
    {
        $this->recordedSubscriptions[] = ['action' => 'create', 'request' => $request];

        if (! empty($this->subscriptionQueue)) {
            return array_shift($this->subscriptionQueue);
        }

        return $this->makeSubscriptionResponse('FAKE-SUB-'.strtoupper(uniqid()), $request->subscriptionId);
    }

    public function getSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $this->recordedSubscriptions[] = ['action' => 'get', 'id' => $providerSubscriptionId];

        if (! empty($this->subscriptionQueue)) {
            return array_shift($this->subscriptionQueue);
        }

        return $this->makeSubscriptionResponse($providerSubscriptionId, $providerSubscriptionId);
    }

    public function updateSubscription(UpdateSubscriptionRequest $request): SubscriptionResponse
    {
        $this->recordedSubscriptions[] = ['action' => 'update', 'request' => $request];

        if (! empty($this->subscriptionQueue)) {
            return array_shift($this->subscriptionQueue);
        }

        return $this->makeSubscriptionResponse($request->providerSubscriptionId, $request->providerSubscriptionId);
    }

    public function pauseSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $this->recordedSubscriptions[] = ['action' => 'pause', 'id' => $providerSubscriptionId];

        if (! empty($this->subscriptionQueue)) {
            return array_shift($this->subscriptionQueue);
        }

        return $this->makeSubscriptionResponse($providerSubscriptionId, $providerSubscriptionId, SubscriptionStatus::Inactive);
    }

    public function resumeSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $this->recordedSubscriptions[] = ['action' => 'resume', 'id' => $providerSubscriptionId];

        if (! empty($this->subscriptionQueue)) {
            return array_shift($this->subscriptionQueue);
        }

        return $this->makeSubscriptionResponse($providerSubscriptionId, $providerSubscriptionId, SubscriptionStatus::Active);
    }

    public function cancelSubscription(string $providerSubscriptionId): SubscriptionResponse
    {
        $this->recordedSubscriptions[] = ['action' => 'cancel', 'id' => $providerSubscriptionId];

        if (! empty($this->subscriptionQueue)) {
            return array_shift($this->subscriptionQueue);
        }

        return $this->makeSubscriptionResponse($providerSubscriptionId, $providerSubscriptionId, SubscriptionStatus::Inactive);
    }

    public function verifyWebhook(Request $request): bool
    {
        return $this->webhookValid;
    }

    public function parseWebhook(Request $request): NormalizedWebhook
    {
        if ($this->webhookResponse !== null) {
            return $this->webhookResponse;
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        return new NormalizedWebhook(
            provider: 'fake',
            merchantOrderId: $payload['merchant_order_id'] ?? 'FAKE-ORDER',
            status: PaymentStatus::Paid,
            signatureValid: true,
            rawPayload: $payload,
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    protected function makeStatusResponse(string $merchantOrderId, PaymentStatus $status): StatusResponse
    {
        return StatusResponse::make([
            'provider_name' => 'fake',
            'provider_transaction_id' => 'FAKE-TRX-'.strtoupper(uniqid()),
            'merchant_order_id' => $merchantOrderId,
            'status' => $status,
            'raw_response' => [],
        ]);
    }

    protected function makeSubscriptionResponse(
        string $providerSubscriptionId,
        string $subscriptionId,
        SubscriptionStatus $status = SubscriptionStatus::Active,
    ): SubscriptionResponse {
        return new SubscriptionResponse(
            providerName: 'fake',
            providerSubscriptionId: $providerSubscriptionId,
            subscriptionId: $subscriptionId,
            name: 'Fake Subscription',
            status: $status,
            amount: 0,
            currency: 'IDR',
            interval: SubscriptionInterval::Month,
            intervalCount: 1,
            rawResponse: [],
        );
    }
}
