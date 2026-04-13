<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\StatusResponse;

interface SupportsCancel
{
    /**
     * Batalkan transaksi yang masih dalam status pending atau authorized.
     */
    public function cancel(string $merchantOrderId): StatusResponse;
}
