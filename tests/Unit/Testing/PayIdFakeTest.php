<?php

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Aliziodev\PayId\Laravel\Facades\PayId;
use Aliziodev\PayId\Testing\FakeDriver;

describe('PayIdFake', function () {

    beforeEach(function () {
        app('payid')->extend('fake', fn () => new FakeDriver);
    });

    it('can fake a charge response', function () {
        $fake = PayId::fake();

        $fake->fakeCharge(ChargeResponse::make([
            'provider_name' => 'fake',
            'provider_transaction_id' => 'TRX-FAKE-001',
            'merchant_order_id' => 'ORDER-001',
            'status' => PaymentStatus::Pending,
            'payment_url' => 'https://fake.test/pay',
            'raw_response' => [],
        ]));

        $response = app('payid')->charge(ChargeRequest::make([
            'merchant_order_id' => 'ORDER-001',
            'amount' => 100000,
            'channel' => PaymentChannel::Qris,
            'customer' => ['name' => 'Budi', 'email' => 'budi@example.com'],
        ]));

        expect($response->providerTransactionId)->toBe('TRX-FAKE-001');
        expect($response->paymentUrl)->toBe('https://fake.test/pay');
        expect($response->status)->toBe(PaymentStatus::Pending);
    });

    it('returns default fake response when queue is empty', function () {
        PayId::fake();

        $response = app('payid')->charge(ChargeRequest::make([
            'merchant_order_id' => 'ORDER-002',
            'amount' => 50000,
            'channel' => PaymentChannel::Gopay,
            'customer' => ['name' => 'Ani', 'email' => 'ani@example.com'],
        ]));

        expect($response)->toBeInstanceOf(ChargeResponse::class);
        expect($response->status)->toBe(PaymentStatus::Pending);
        expect($response->providerName)->toBe('fake');
    });

    it('can queue multiple charge responses in order', function () {
        $fake = PayId::fake();

        $fake->fakeCharge(ChargeResponse::make([
            'provider_name' => 'fake',
            'provider_transaction_id' => 'TRX-001',
            'merchant_order_id' => 'ORDER-001',
            'status' => PaymentStatus::Pending,
            'raw_response' => [],
        ]));

        $fake->fakeCharge(ChargeResponse::make([
            'provider_name' => 'fake',
            'provider_transaction_id' => 'TRX-002',
            'merchant_order_id' => 'ORDER-002',
            'status' => PaymentStatus::Paid,
            'raw_response' => [],
        ]));

        $manager = app('payid');

        $first = $manager->charge(ChargeRequest::make(['merchant_order_id' => 'ORDER-001', 'amount' => 100000, 'channel' => PaymentChannel::Qris, 'customer' => ['name' => 'A', 'email' => 'a@test.com']]));
        $second = $manager->charge(ChargeRequest::make(['merchant_order_id' => 'ORDER-002', 'amount' => 200000, 'channel' => PaymentChannel::Qris, 'customer' => ['name' => 'B', 'email' => 'b@test.com']]));

        expect($first->providerTransactionId)->toBe('TRX-001');
        expect($second->providerTransactionId)->toBe('TRX-002');
    });

    it('fake supports all capabilities', function () {
        $fake = PayId::fake();

        expect($fake->supports(Capability::Charge))->toBeTrue();
        expect($fake->supports(Capability::Status))->toBeTrue();
        expect($fake->supports(Capability::Refund))->toBeTrue();
        expect($fake->supports(Capability::Cancel))->toBeTrue();
        expect($fake->supports(Capability::Expire))->toBeTrue();
        expect($fake->supports(Capability::WebhookVerification))->toBeTrue();
        expect($fake->supports(Capability::WebhookParsing))->toBeTrue();
    });

});
