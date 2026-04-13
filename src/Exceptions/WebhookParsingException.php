<?php

namespace Aliziodev\PayId\Exceptions;

use Throwable;

class WebhookParsingException extends PayIdException
{
    public function __construct(string $driver, string $message, ?Throwable $previous = null)
    {
        parent::__construct(
            "PayID webhook parsing failed for driver [{$driver}]: {$message}",
            previous: $previous,
        );

        $this->withContext(['driver' => $driver]);
    }
}
