<?php

namespace Aliziodev\PayId\Exceptions;

class InvalidCredentialException extends ConfigurationException
{
    public function __construct(string $driver, string $field)
    {
        parent::__construct(
            "PayID driver [{$driver}] has invalid or missing credential: [{$field}].",
        );

        $this->withContext(['driver' => $driver, 'field' => $field]);
    }
}
