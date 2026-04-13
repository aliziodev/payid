<?php

namespace Aliziodev\PayId\Exceptions;

class MissingDriverConfigException extends ConfigurationException
{
    public function __construct(string $driver)
    {
        parent::__construct(
            "PayID driver [{$driver}] is not configured. Add it to config/payid.php under 'drivers'.",
        );

        $this->withContext(['driver' => $driver]);
    }
}
