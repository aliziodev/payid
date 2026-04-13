<?php

use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Events\WebhookVerificationFailed;
use Aliziodev\PayId\Managers\PayIdManager;
use Aliziodev\PayId\Testing\FakeDriver;
use Aliziodev\PayId\Webhooks\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

describe('WebhookProcessor', function () {

    beforeEach(function () {
        $ledgerSpy = new class
        {
            public array $webhookEvents = [];

            public array $statusSnapshots = [];

            public array $processedMarks = [];

            public function recordWebhookEvent(array $attributes): object
            {
                $this->webhookEvents[] = $attributes;

                return (object) ['id' => uniqid('event_', true)];
            }

            public function upsertStatus(array $attributes): object
            {
                $this->statusSnapshots[] = $attributes;

                return (object) $attributes;
            }

            public function markWebhookProcessed(object $event, bool $success, ?string $message = null): object
            {
                $this->processedMarks[] = [
                    'event_id' => $event->id ?? null,
                    'success' => $success,
                    'message' => $message,
                ];

                return $event;
            }
        };

        app()->instance('payid-transactions.ledger', $ledgerSpy);
        app()->instance('payid-transactions.ledger.spy', $ledgerSpy);

        app('payid')->extend('fake', fn () => new FakeDriver);
        app('config')->set('payid.drivers.fake', ['driver' => 'fake']);
    });

    it('dispatches WebhookReceived event for valid webhook', function () {
        Event::fake([WebhookReceived::class]);

        $request = Request::create('/payid/webhook/fake', 'POST', [], [], [], [],
            json_encode(['merchant_order_id' => 'ORDER-001'])
        );
        $request->headers->set('Content-Type', 'application/json');

        $processor = app(WebhookProcessor::class);
        $result = $processor->handle($request, 'fake');

        /** @var object{webhookEvents: array<int, array<string, mixed>>, statusSnapshots: array<int, array<string, mixed>>, processedMarks: array<int, array<string, mixed>>} $ledgerSpy */
        $ledgerSpy = app('payid-transactions.ledger.spy');

        expect($result->success)->toBeTrue();
        expect($result->httpStatus)->toBe(200);
        expect($ledgerSpy->webhookEvents)->toHaveCount(1)
            ->and($ledgerSpy->statusSnapshots)->toHaveCount(1)
            ->and($ledgerSpy->processedMarks)->toHaveCount(1)
            ->and($ledgerSpy->processedMarks[0]['success'])->toBeTrue();

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) {
            return $event->webhook->merchantOrderId === 'ORDER-001'
                && $event->webhook->status === PaymentStatus::Paid;
        });
    });

    it('returns 401 and dispatches WebhookVerificationFailed for invalid signature', function () {
        Event::fake([WebhookVerificationFailed::class]);

        // Buat fake driver dengan verifikasi gagal
        $failDriver = new FakeDriver;
        $failDriver->setWebhookValid(false);

        app('config')->set('payid.drivers.fail_fake', ['driver' => 'fail_fake']);
        app('payid')->extend('fail_fake', fn () => $failDriver);

        $request = Request::create('/payid/webhook/fail_fake', 'POST', [], [], [], [],
            json_encode(['merchant_order_id' => 'ORDER-BAD'])
        );

        $processor = app(WebhookProcessor::class);
        $result = $processor->handle($request, 'fail_fake');

        /** @var object{webhookEvents: array<int, array<string, mixed>>, statusSnapshots: array<int, array<string, mixed>>, processedMarks: array<int, array<string, mixed>>} $ledgerSpy */
        $ledgerSpy = app('payid-transactions.ledger.spy');

        expect($result->success)->toBeFalse();
        expect($result->httpStatus)->toBe(401);
        expect($ledgerSpy->webhookEvents)->toHaveCount(1)
            ->and($ledgerSpy->statusSnapshots)->toHaveCount(0)
            ->and($ledgerSpy->processedMarks)->toHaveCount(1)
            ->and($ledgerSpy->processedMarks[0]['success'])->toBeFalse();

        Event::assertDispatched(WebhookVerificationFailed::class);
    });

    it('uses custom webhook response from fake driver', function () {
        Event::fake([WebhookReceived::class]);

        $customWebhook = new NormalizedWebhook(
            provider: 'fake',
            merchantOrderId: 'ORDER-CUSTOM',
            status: PaymentStatus::Expired,
            signatureValid: true,
            rawPayload: ['custom' => true],
        );

        $driver = new FakeDriver;
        $driver->setWebhookResponse($customWebhook);

        app('config')->set('payid.drivers.custom_fake', ['driver' => 'custom_fake']);
        app('payid')->extend('custom_fake', fn () => $driver);

        $request = Request::create('/payid/webhook/custom_fake', 'POST');

        $processor = app(WebhookProcessor::class);
        $result = $processor->handle($request, 'custom_fake');

        /** @var object{statusSnapshots: array<int, array<string, mixed>>} $ledgerSpy */
        $ledgerSpy = app('payid-transactions.ledger.spy');

        expect($result->success)->toBeTrue();
        expect($ledgerSpy->statusSnapshots)->toHaveCount(1)
            ->and($ledgerSpy->statusSnapshots[0]['merchant_order_id'])->toBe('ORDER-CUSTOM');

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) {
            return $event->webhook->merchantOrderId === 'ORDER-CUSTOM'
                && $event->webhook->status === PaymentStatus::Expired;
        });
    });

    it('keeps webhook flow successful when ledger throws', function () {
        $failingLedger = new class
        {
            public function recordWebhookEvent(array $attributes): never
            {
                throw new RuntimeException('ledger unavailable');
            }

            public function upsertStatus(array $attributes): never
            {
                throw new RuntimeException('ledger unavailable');
            }

            public function markWebhookProcessed(object $event, bool $success, ?string $message = null): object
            {
                return $event;
            }
        };

        app()->instance('payid-transactions.ledger', $failingLedger);
        app()->forgetInstance(PayIdManager::class);
        app()->forgetInstance(WebhookProcessor::class);

        $request = Request::create('/payid/webhook/fake', 'POST', [], [], [], [],
            json_encode(['merchant_order_id' => 'ORDER-RESILIENT-001'])
        );
        $request->headers->set('Content-Type', 'application/json');

        $result = app(WebhookProcessor::class)->handle($request, 'fake');

        expect($result->success)->toBeTrue()
            ->and($result->httpStatus)->toBe(200);
    });

});
