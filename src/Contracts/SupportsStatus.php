<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\StatusResponse;

interface SupportsStatus
{
    /**
     * Periksa status transaksi berdasarkan merchant order ID.
     */
    public function status(string $merchantOrderId): StatusResponse;
}
