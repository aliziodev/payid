<?php

namespace Aliziodev\PayId\Exceptions;

use Throwable;

class PayloadMappingException extends PayIdException
{
    public function __construct(string $driver, string $field, ?Throwable $previous = null)
    {
        parent::__construct(
            "PayID driver [{$driver}] failed to map field [{$field}] from provider response.",
            previous: $previous,
        );

        $this->withContext(['driver' => $driver, 'field' => $field]);
    }
}
