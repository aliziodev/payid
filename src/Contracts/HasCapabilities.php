<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\Enums\Capability;

trait HasCapabilities
{
    public function supports(Capability $capability): bool
    {
        return in_array($capability, $this->getCapabilities(), true);
    }
}
