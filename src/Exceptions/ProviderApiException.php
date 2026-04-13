<?php

namespace Aliziodev\PayId\Exceptions;

use Throwable;

class ProviderApiException extends PayIdException
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        protected readonly string $driver,
        string $message,
        protected readonly int $httpStatus = 0,
        protected readonly array $rawResponse = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);

        $this->withContext([
            'driver' => $driver,
            'http_status' => $httpStatus,
        ]);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }
}
