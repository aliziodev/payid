<?php

namespace Aliziodev\PayId\Exceptions;

class DriverNotFoundException extends PayIdException
{
    public function __construct(string $driver)
    {
        parent::__construct(
            "PayID driver [{$driver}] not found. Make sure the driver package is installed and registered.",
        );

        $this->withContext(['driver' => $driver]);
    }
}
