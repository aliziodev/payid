<?php

namespace Aliziodev\PayId\Exceptions;

use Throwable;

class ProviderNetworkException extends PayIdException
{
    public function __construct(string $driver, string $message, ?Throwable $previous = null)
    {
        parent::__construct(
            "PayID driver [{$driver}] network error: {$message}",
            previous: $previous,
        );

        $this->withContext(['driver' => $driver]);
    }
}
