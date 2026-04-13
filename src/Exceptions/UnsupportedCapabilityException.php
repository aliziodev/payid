<?php

namespace Aliziodev\PayId\Exceptions;

use Aliziodev\PayId\Enums\Capability;

class UnsupportedCapabilityException extends PayIdException
{
    public function __construct(string $driver, Capability $capability)
    {
        parent::__construct(
            "PayID driver [{$driver}] does not support capability [{$capability->value}].",
        );

        $this->withContext(['driver' => $driver, 'capability' => $capability->value]);
    }
}
