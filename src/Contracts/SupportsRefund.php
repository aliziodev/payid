<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\RefundRequest;
use Aliziodev\PayId\DTO\RefundResponse;

interface SupportsRefund
{
    /**
     * Proses pengembalian dana (refund) untuk transaksi yang sudah dibayar.
     */
    public function refund(RefundRequest $request): RefundResponse;
}
