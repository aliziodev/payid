<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\StatusResponse;

interface SupportsExpire
{
    /**
     * Paksa transaksi menjadi expired sebelum waktu kadaluarsa alaminya.
     */
    public function expire(string $merchantOrderId): StatusResponse;
}
