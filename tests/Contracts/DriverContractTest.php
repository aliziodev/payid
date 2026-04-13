<?php

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Contracts\SupportsWebhookParsing;
use Aliziodev\PayId\Contracts\SupportsWebhookVerification;
use Aliziodev\PayId\DTO\NormalizedWebhook;
use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Testing\FakeDriver;
use Illuminate\Http\Request;

describe('Driver Contract', function () {

    it('driver name must be non-empty string', function () {
        $driver = new FakeDriver;

        expect($driver)->toBeInstanceOf(DriverInterface::class);
        expect($driver->getName())->toBeString()->not->toBe('');
    });

    it('declares capabilities as Capability enum values without duplicates', function () {
        $driver = new FakeDriver;
        $capabilities = $driver->getCapabilities();

        expect($capabilities)->not->toBeEmpty();

        foreach ($capabilities as $capability) {
            expect($capability)->toBeInstanceOf(Capability::class);
            expect($driver->supports($capability))->toBeTrue();
        }

        $unique = array_unique(array_map(
            static fn (Capability $capability) => $capability->value,
            $capabilities,
        ));

        expect(count($unique))->toBe(count($capabilities));
    });

    it('supports webhook verification and parsing contracts when implemented', function () {
        $driver = new FakeDriver;

        expect($driver)->toBeInstanceOf(SupportsWebhookVerification::class);
        expect($driver)->toBeInstanceOf(SupportsWebhookParsing::class);

        $request = Request::create('/payid/webhook/fake', 'POST', [], [], [], [], json_encode([
            'merchant_order_id' => 'ORDER-CONTRACT-001',
        ]));

        expect($driver->verifyWebhook($request))->toBeTrue();

        $normalized = $driver->parseWebhook($request);

        expect($normalized)->toBeInstanceOf(NormalizedWebhook::class);
        expect($normalized->merchantOrderId)->toBe('ORDER-CONTRACT-001');
    });
});
