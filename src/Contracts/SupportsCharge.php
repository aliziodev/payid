<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;

interface SupportsCharge
{
    /**
     * Buat transaksi pembayaran baru.
     */
    public function charge(ChargeRequest $request): ChargeResponse;
}
