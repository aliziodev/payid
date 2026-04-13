<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\DTO\SubscriptionRequest;
use Aliziodev\PayId\DTO\SubscriptionResponse;
use Aliziodev\PayId\DTO\UpdateSubscriptionRequest;

interface SupportsSubscription
{
    /**
     * Buat subscription baru. Provider akan otomatis charge pada interval yang ditentukan.
     */
    public function createSubscription(SubscriptionRequest $request): SubscriptionResponse;

    /**
     * Ambil detail subscription berdasarkan ID provider.
     */
    public function getSubscription(string $providerSubscriptionId): SubscriptionResponse;

    /**
     * Update subscription (amount, interval, token, dll).
     */
    public function updateSubscription(UpdateSubscriptionRequest $request): SubscriptionResponse;

    /**
     * Hentikan sementara subscription (tidak akan charge sampai di-resume).
     */
    public function pauseSubscription(string $providerSubscriptionId): SubscriptionResponse;

    /**
     * Aktifkan kembali subscription yang sedang di-pause.
     */
    public function resumeSubscription(string $providerSubscriptionId): SubscriptionResponse;

    /**
     * Batalkan subscription secara permanen. Tidak dapat di-undo.
     */
    public function cancelSubscription(string $providerSubscriptionId): SubscriptionResponse;
}
