<?php

namespace Aliziodev\PayId\Exceptions;

use Throwable;

class ProviderResponseException extends PayIdException
{
    public function __construct(string $driver, string $message, ?Throwable $previous = null)
    {
        parent::__construct(
            "PayID driver [{$driver}] received an unparseable response: {$message}",
            previous: $previous,
        );

        $this->withContext(['driver' => $driver]);
    }
}
