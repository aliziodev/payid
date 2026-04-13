<?php

use Aliziodev\PayId\Exceptions\ProviderApiException;
use Aliziodev\PayId\Exceptions\ProviderNetworkException;
use Aliziodev\PayId\Support\Http\PayIdHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

describe('PayIdHttpClient', function () {

    it('sends GET request and returns decoded json response', function () {
        Http::fake([
            'https://api.example.test/orders*' => Http::response([
                'ok' => true,
                'id' => 'ORDER-001',
            ], 200),
        ]);

        $client = new PayIdHttpClient(
            driver: 'fake',
            baseUrl: 'https://api.example.test',
        );

        $result = $client->get('/orders', ['id' => 'ORDER-001'], ['X-Token' => 'abc']);

        expect($result['ok'])->toBeTrue();
        expect($result['id'])->toBe('ORDER-001');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.example.test/orders?id=ORDER-001'
                && $request->hasHeader('X-Token', 'abc');
        });
    });

    it('throws ProviderApiException on failed response', function () {
        Http::fake([
            'https://api.example.test/charge' => Http::response([
                'error' => 'invalid request',
            ], 422),
        ]);

        $client = new PayIdHttpClient(
            driver: 'fake',
            baseUrl: 'https://api.example.test',
        );

        try {
            $client->post('/charge', ['amount' => 100000]);

            test()->fail('Expected ProviderApiException was not thrown.');
        } catch (ProviderApiException $e) {
            expect($e->getMessage())->toContain('HTTP 422 from provider.');
            expect($e->getHttpStatus())->toBe(422);
            expect($e->getRawResponse())->toBe(['error' => 'invalid request']);
        }
    });

    it('throws ProviderNetworkException on connection error', function () {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $client = new PayIdHttpClient(
            driver: 'fake',
            baseUrl: 'https://api.example.test',
        );

        expect(fn () => $client->get('/status'))
            ->toThrow(ProviderNetworkException::class);
    });

    it('returns empty array for successful non-json response body', function () {
        Http::fake([
            'https://api.example.test/health' => Http::response('OK', 200),
        ]);

        $client = new PayIdHttpClient(
            driver: 'fake',
            baseUrl: 'https://api.example.test',
        );

        $result = $client->get('/health');

        expect($result)->toBe([]);
    });
});
