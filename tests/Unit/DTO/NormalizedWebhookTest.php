<?php

use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\Enums\PaymentChannel;
use Aliziodev\PayId\Enums\PaymentStatus;
use Carbon\Carbon;

describe('NormalizedWebhook', function () {

    it('can be instantiated with required fields only', function () {
        $webhook = new NormalizedWebhook(
            provider: 'midtrans',
            merchantOrderId: 'ORDER-001',
            status: PaymentStatus::Paid,
            signatureValid: true,
            rawPayload: ['transaction_status' => 'settlement'],
        );

        expect($webhook->provider)->toBe('midtrans');
        expect($webhook->merchantOrderId)->toBe('ORDER-001');
        expect($webhook->status)->toBe(PaymentStatus::Paid);
        expect($webhook->signatureValid)->toBeTrue();
        expect($webhook->rawPayload)->toBe(['transaction_status' => 'settlement']);
    });

    it('can be instantiated with all optional fields', function () {
        $occurredAt = Carbon::now();

        $webhook = new NormalizedWebhook(
            provider: 'xendit',
            merchantOrderId: 'ORDER-002',
            status: PaymentStatus::Paid,
            signatureValid: true,
            rawPayload: [],
            providerTransactionId: 'XENDIT-TRX-001',
            eventType: 'invoice.paid',
            amount: 150000,
            currency: 'IDR',
            channel: PaymentChannel::Qris,
            occurredAt: $occurredAt,
        );

        expect($webhook->providerTransactionId)->toBe('XENDIT-TRX-001');
        expect($webhook->eventType)->toBe('invoice.paid');
        expect($webhook->amount)->toBe(150000);
        expect($webhook->currency)->toBe('IDR');
        expect($webhook->channel)->toBe(PaymentChannel::Qris);
        expect($webhook->occurredAt)->toBe($occurredAt);
    });

    it('reflects invalid signature', function () {
        $webhook = new NormalizedWebhook(
            provider: 'midtrans',
            merchantOrderId: 'ORDER-003',
            status: PaymentStatus::Pending,
            signatureValid: false,
            rawPayload: [],
        );

        expect($webhook->signatureValid)->toBeFalse();
    });

});
