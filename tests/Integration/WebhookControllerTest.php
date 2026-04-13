<?php

use Aliziodev\PayId\Testing\FakeDriver;

describe('WebhookController', function () {

    beforeEach(function () {
        app('payid')->extend('fake', fn () => new FakeDriver);
        app('config')->set('payid.drivers.fake', ['driver' => 'fake']);
    });

    it('returns 200 OK when webhook is valid', function () {
        $response = $this->postJson('/payid/webhook/fake', [
            'merchant_order_id' => 'ORDER-CTRL-001',
        ]);

        $response->assertOk();
        $response->assertSeeText('OK');
    });

    it('returns 401 when signature verification fails', function () {
        $driver = new FakeDriver;
        $driver->setWebhookValid(false);

        app('payid')->extend('fake_invalid', fn () => $driver);
        app('config')->set('payid.drivers.fake_invalid', ['driver' => 'fake_invalid']);

        $response = $this->postJson('/payid/webhook/fake_invalid', [
            'merchant_order_id' => 'ORDER-CTRL-INVALID',
        ]);

        $response->assertStatus(401);
        $response->assertSeeText('Webhook signature verification failed.');
    });
});
