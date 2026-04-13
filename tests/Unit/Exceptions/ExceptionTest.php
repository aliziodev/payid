<?php

use Aliziodev\PayId\Enums\Capability;
use Aliziodev\PayId\Exceptions\DriverNotFoundException;
use Aliziodev\PayId\Exceptions\DriverResolutionException;
use Aliziodev\PayId\Exceptions\InvalidCredentialException;
use Aliziodev\PayId\Exceptions\MissingDriverConfigException;
use Aliziodev\PayId\Exceptions\PayIdException;
use Aliziodev\PayId\Exceptions\ProviderApiException;
use Aliziodev\PayId\Exceptions\UnsupportedCapabilityException;
use Aliziodev\PayId\Exceptions\WebhookVerificationException;

describe('Exception hierarchy', function () {

    it('all exceptions extend PayIdException', function () {
        expect(new MissingDriverConfigException('midtrans'))->toBeInstanceOf(PayIdException::class);
        expect(new InvalidCredentialException('midtrans', 'server_key'))->toBeInstanceOf(PayIdException::class);
        expect(new DriverNotFoundException('midtrans'))->toBeInstanceOf(PayIdException::class);
        expect(new UnsupportedCapabilityException('midtrans', Capability::Refund))->toBeInstanceOf(PayIdException::class);
        expect(new WebhookVerificationException('midtrans'))->toBeInstanceOf(PayIdException::class);
    });

    it('MissingDriverConfigException has correct message', function () {
        $e = new MissingDriverConfigException('xendit');
        expect($e->getMessage())->toContain('xendit');
        expect($e->getContext())->toBe(['driver' => 'xendit']);
    });

    it('InvalidCredentialException has correct message', function () {
        $e = new InvalidCredentialException('midtrans', 'server_key');
        expect($e->getMessage())->toContain('midtrans');
        expect($e->getMessage())->toContain('server_key');
    });

    it('DriverNotFoundException has correct message', function () {
        $e = new DriverNotFoundException('doku');
        expect($e->getMessage())->toContain('doku');
    });

    it('UnsupportedCapabilityException has correct message', function () {
        $e = new UnsupportedCapabilityException('ipaymu', Capability::Refund);
        expect($e->getMessage())->toContain('ipaymu');
        expect($e->getMessage())->toContain('refund');
        expect($e->getContext()['capability'])->toBe('refund');
    });

    it('ProviderApiException exposes driver, http status, and raw response', function () {
        $e = new ProviderApiException(
            driver: 'midtrans',
            message: 'Bad request',
            httpStatus: 400,
            rawResponse: ['error_messages' => ['Invalid input']],
        );

        expect($e->getDriver())->toBe('midtrans');
        expect($e->getHttpStatus())->toBe(400);
        expect($e->getRawResponse())->toBe(['error_messages' => ['Invalid input']]);
    });

    it('DriverResolutionException wraps original exception', function () {
        $original = new RuntimeException('Connection refused');
        $e = new DriverResolutionException('midtrans', $original);

        expect($e->getPrevious())->toBe($original);
        expect($e->getMessage())->toContain('midtrans');
    });

    it('PayIdException supports withContext()', function () {
        $e = (new PayIdException('Test error'))->withContext(['key' => 'value']);
        expect($e->getContext())->toBe(['key' => 'value']);
    });

});
