<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\StatusResponse;

interface SupportsApprove
{
    /**
     * Setujui transaksi yang ditandai sebagai fraud challenge.
     * Berlaku untuk kartu kredit yang di-hold oleh sistem anti-fraud provider.
     */
    public function approve(string $merchantOrderId): StatusResponse;
}
