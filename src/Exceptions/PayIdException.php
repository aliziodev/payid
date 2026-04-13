<?php

namespace Aliziodev\PayId\Exceptions;

use RuntimeException;

class PayIdException extends RuntimeException
{
    /** @var array<string, mixed> */
    protected array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
