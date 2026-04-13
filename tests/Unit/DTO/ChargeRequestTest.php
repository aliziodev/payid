<?php

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\CustomerData;
use Aliziodev\PayId\DTO\ItemData;
use Aliziodev\PayId\Enums\PaymentChannel;

describe('ChargeRequest', function () {

    it('can be created via make() with minimal data', function () {
        $request = ChargeRequest::make([
            'merchant_order_id' => 'ORDER-001',
            'amount' => 150000,
            'channel' => 'qris',
            'customer' => ['name' => 'Budi', 'email' => 'budi@example.com'],
        ]);

        expect($request->merchantOrderId)->toBe('ORDER-001');
        expect($request->amount)->toBe(150000);
        expect($request->currency)->toBe('IDR');
        expect($request->channel)->toBe(PaymentChannel::Qris);
        expect($request->customer->name)->toBe('Budi');
        expect($request->customer->email)->toBe('budi@example.com');
        expect($request->items)->toBeEmpty();
        expect($request->metadata)->toBeEmpty();
    });

    it('can be created with PaymentChannel enum directly', function () {
        $request = ChargeRequest::make([
            'merchant_order_id' => 'ORDER-002',
            'amount' => 100000,
            'channel' => PaymentChannel::VaBca,
            'customer' => ['name' => 'Ani', 'email' => 'ani@example.com'],
        ]);

        expect($request->channel)->toBe(PaymentChannel::VaBca);
    });

    it('can be created with CustomerData object directly', function () {
        $customer = new CustomerData(name: 'Sari', email: 'sari@example.com');

        $request = ChargeRequest::make([
            'merchant_order_id' => 'ORDER-003',
            'amount' => 50000,
            'channel' => PaymentChannel::Gopay,
            'customer' => $customer,
        ]);

        expect($request->customer)->toBe($customer);
    });

    it('maps items correctly', function () {
        $request = ChargeRequest::make([
            'merchant_order_id' => 'ORDER-004',
            'amount' => 200000,
            'channel' => 'qris',
            'customer' => ['name' => 'Tono', 'email' => 'tono@example.com'],
            'items' => [
                ['id' => 'ITEM-1', 'name' => 'Kopi', 'price' => 20000, 'quantity' => 2],
                ['id' => 'ITEM-2', 'name' => 'Teh', 'price' => 15000, 'quantity' => 1],
            ],
        ]);

        expect($request->items)->toHaveCount(2);
        expect($request->items[0])->toBeInstanceOf(ItemData::class);
        expect($request->items[0]->name)->toBe('Kopi');
        expect($request->items[0]->total())->toBe(40000);
    });

    it('accepts optional fields', function () {
        $request = ChargeRequest::make([
            'merchant_order_id' => 'ORDER-005',
            'amount' => 75000,
            'channel' => 'payment_link',
            'customer' => ['name' => 'Dian', 'email' => 'dian@example.com'],
            'description' => 'Pembelian produk X',
            'callback_url' => 'https://example.com/callback',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
            'metadata' => ['order_source' => 'web'],
        ]);

        expect($request->description)->toBe('Pembelian produk X');
        expect($request->callbackUrl)->toBe('https://example.com/callback');
        expect($request->successUrl)->toBe('https://example.com/success');
        expect($request->failureUrl)->toBe('https://example.com/failure');
        expect($request->metadata)->toBe(['order_source' => 'web']);
    });

    it('is immutable (readonly)', function () {
        $request = ChargeRequest::make([
            'merchant_order_id' => 'ORDER-006',
            'amount' => 100000,
            'channel' => PaymentChannel::Qris,
            'customer' => ['name' => 'Rio', 'email' => 'rio@example.com'],
        ]);

        expect(fn () => $request->amount = 999)->toThrow(Error::class);
    });

});
