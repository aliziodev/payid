<?php

namespace Aliziodev\PayId\Exceptions;

class WebhookVerificationException extends PayIdException
{
    public function __construct(string $driver, string $reason = 'Signature mismatch')
    {
        parent::__construct(
            "PayID webhook verification failed for driver [{$driver}]: {$reason}",
        );

        $this->withContext(['driver' => $driver, 'reason' => $reason]);
    }
}
