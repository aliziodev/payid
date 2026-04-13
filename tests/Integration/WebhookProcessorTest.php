<?php

use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Events\WebhookVerificationFailed;
use Aliziodev\PayId\Testing\FakeDriver;
use Aliziodev\PayId\Webhooks\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

describe('WebhookProcessor', function () {

    beforeEach(function () {
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

        expect($result->success)->toBeTrue();
        expect($result->httpStatus)->toBe(200);

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

        expect($result->success)->toBeFalse();
        expect($result->httpStatus)->toBe(401);

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

        expect($result->success)->toBeTrue();

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) {
            return $event->webhook->merchantOrderId === 'ORDER-CUSTOM'
                && $event->webhook->status === PaymentStatus::Expired;
        });
    });

});
