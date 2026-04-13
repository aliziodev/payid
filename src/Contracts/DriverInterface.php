<?php

namespace Aliziodev\PayId\Contracts;

use Aliziodev\PayId\Enums\Capability;

interface DriverInterface
{
    /**
     * Nama unik driver, digunakan sebagai identifier di konfigurasi dan routing.
     *
     * @example 'midtrans', 'xendit', 'doku'
     */
    public function getName(): string;

    /**
     * Daftar capabilities yang didukung oleh driver ini.
     *
     * @return Capability[]
     */
    public function getCapabilities(): array;

    /**
     * Periksa apakah driver mendukung capability tertentu.
     */
    public function supports(Capability $capability): bool;
}
