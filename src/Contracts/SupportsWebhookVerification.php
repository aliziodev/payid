<?php

namespace Aliziodev\PayId\Contracts;

use Illuminate\Http\Request;

interface SupportsWebhookVerification
{
    /**
     * Verifikasi keaslian webhook dari provider.
     *
     * PENTING: Implementasi harus menggunakan raw request body via
     * $request->getContent(), bukan JSON yang sudah di-parse, karena
     * sebagian besar provider menghitung signature dari raw body.
     */
    public function verifyWebhook(Request $request): bool;
}
