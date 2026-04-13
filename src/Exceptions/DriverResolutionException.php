<?php

namespace Aliziodev\PayId\Exceptions;

use Throwable;

class DriverResolutionException extends PayIdException
{
    public function __construct(string $driver, Throwable $previous)
    {
        parent::__construct(
            "PayID failed to resolve driver [{$driver}]: {$previous->getMessage()}",
            previous: $previous,
        );

        $this->withContext(['driver' => $driver]);
    }
}
