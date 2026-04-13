<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\ChargeRequest;
use Aliziodev\PayId\DTO\ChargeResponse;

interface SupportsDirectCharge
{
    /**
     * Buat transaksi melalui Core API provider.
     * Response mengandung VA number / QR string / action URL langsung — tanpa redirect.
     * Gunakan ini ketika kamu ingin tampilkan instruksi pembayaran sendiri di UI.
     */
    public function directCharge(ChargeRequest $request): ChargeResponse;
}
