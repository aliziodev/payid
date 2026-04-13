<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\NormalizedWebhook;
use Illuminate\Http\Request;

interface SupportsWebhookParsing
{
    /**
     * Parse dan normalisasi payload webhook provider ke format standar PayID.
     *
     * Method ini dipanggil setelah verifikasi signature berhasil (atau jika
     * driver tidak mengimplementasikan SupportsWebhookVerification).
     */
    public function parseWebhook(Request $request): NormalizedWebhook;
}
