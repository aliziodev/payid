<?php

use Aliziodev\PayId\DTO\ChargeResponse;
use Aliziodev\PayId\Enums\PaymentStatus;

describe('ChargeResponse', function () {

    it('can be created via make()', function () {
        $response = ChargeResponse::make([
            'provider_name' => 'midtrans',
            'provider_transaction_id' => 'TRX-001',
            'merchant_order_id' => 'ORDER-001',
            'status' => 'pending',
            'payment_url' => 'https://pay.example.com/pay',
            'raw_response' => ['foo' => 'bar'],
        ]);

        expect($response->providerName)->toBe('midtrans');
        expect($response->providerTransactionId)->toBe('TRX-001');
        expect($response->merchantOrderId)->toBe('ORDER-001');
        expect($response->status)->toBe(PaymentStatus::Pending);
        expect($response->paymentUrl)->toBe('https://pay.example.com/pay');
        expect($response->rawResponse)->toBe(['foo' => 'bar']);
    });

    it('accepts PaymentStatus enum directly', function () {
        $response = ChargeResponse::make([
            'provider_name' => 'xendit',
            'provider_transaction_id' => 'TRX-002',
            'merchant_order_id' => 'ORDER-002',
            'status' => PaymentStatus::Paid,
            'raw_response' => [],
        ]);

        expect($response->status)->toBe(PaymentStatus::Paid);
    });

    it('has nullable optional fields by default', function () {
        $response = ChargeResponse::make([
            'provider_name' => 'xendit',
            'provider_transaction_id' => 'TRX-003',
            'merchant_order_id' => 'ORDER-003',
            'status' => PaymentStatus::Pending,
            'raw_response' => [],
        ]);

        expect($response->paymentUrl)->toBeNull();
        expect($response->qrString)->toBeNull();
        expect($response->vaNumber)->toBeNull();
        expect($response->vaBankCode)->toBeNull();
        expect($response->expiresAt)->toBeNull();
    });

    it('is immutable (readonly)', function () {
        $response = ChargeResponse::make([
            'provider_name' => 'midtrans',
            'provider_transaction_id' => 'TRX-004',
            'merchant_order_id' => 'ORDER-004',
            'status' => PaymentStatus::Pending,
            'raw_response' => [],
        ]);

        expect(fn () => $response->status = PaymentStatus::Paid)->toThrow(Error::class);
    });

});
