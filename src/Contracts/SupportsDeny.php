<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\StatusResponse;

interface SupportsDeny
{
    /**
     * Tolak transaksi yang ditandai sebagai fraud challenge.
     * Berlaku untuk kartu kredit yang di-hold oleh sistem anti-fraud provider.
     */
    public function deny(string $merchantOrderId): StatusResponse;
}
