<?php

namespace Aliziodev\PayId\Support\Http;

use Aliziodev\PayId\Exceptions\ProviderApiException;
use Aliziodev\PayId\Exceptions\ProviderNetworkException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class PayIdHttpClient
{
    public function __construct(
        protected readonly string $driver,
        protected readonly string $baseUrl,
        protected readonly int $timeout = 30,
        protected readonly int $retryTimes = 1,
        protected readonly int $retryDelayMs = 500,
    ) {}

    /**
     * Buat PendingRequest dengan konfigurasi dasar (timeout, retry, baseUrl).
     * Gunakan ini untuk menambahkan headers atau auth sebelum mengirim request.
     */
    public function pending(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retryDelayMs);
    }

    /**
     * Kirim GET request dan kembalikan decoded JSON.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws ProviderNetworkException
     * @throws ProviderApiException
     */
    public function get(string $path, array $query = [], array $headers = []): array
    {
        return $this->send('get', $path, $query, $headers);
    }

    /**
     * Kirim POST request dan kembalikan decoded JSON.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws ProviderNetworkException
     * @throws ProviderApiException
     */
    public function post(string $path, array $data = [], array $headers = []): array
    {
        return $this->send('post', $path, $data, $headers);
    }

    /**
     * Kirim PATCH request dan kembalikan decoded JSON.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws ProviderNetworkException
     * @throws ProviderApiException
     */
    public function patch(string $path, array $data = [], array $headers = []): array
    {
        return $this->send('patch', $path, $data, $headers);
    }

    /**
     * Kirim DELETE request dan kembalikan decoded JSON.
     *
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws ProviderNetworkException
     * @throws ProviderApiException
     */
    public function delete(string $path, array $headers = []): array
    {
        return $this->send('delete', $path, [], $headers);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, array $data, array $headers): array
    {
        try {
            $request = $this->pending()->withHeaders($headers);

            /** @var Response $response */
            $response = match ($method) {
                'get' => $request->get($path, $data),
                'post' => $request->post($path, $data),
                'patch' => $request->patch($path, $data),
                'delete' => $request->delete($path),
                default => $request->post($path, $data),
            };
        } catch (ConnectionException $e) {
            throw new ProviderNetworkException($this->driver, $e->getMessage(), $e);
        } catch (Throwable $e) {
            throw new ProviderNetworkException($this->driver, $e->getMessage(), $e);
        }

        if ($response->failed()) {
            throw new ProviderApiException(
                driver: $this->driver,
                message: "HTTP {$response->status()} from provider.",
                httpStatus: $response->status(),
                rawResponse: $this->safeJson($response),
            );
        }

        return $this->safeJson($response);
    }

    /**
     * @return array<string, mixed>
     */
    protected function safeJson(Response $response): array
    {
        try {
            return $response->json() ?? [];
        } catch (Throwable) {
            return [];
        }
    }
}
