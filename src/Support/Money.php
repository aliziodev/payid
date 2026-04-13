<?php

namespace Aliziodev\PayId\Support;

class Money
{
    /**
     * Format nominal ke string dengan pemisah ribuan.
     * Contoh: 150000 → "150.000"
     */
    public static function format(int $amount, string $thousandsSep = '.', string $decimalSep = ','): string
    {
        return number_format($amount, 0, $decimalSep, $thousandsSep);
    }

    /**
     * Format nominal ke string dengan simbol mata uang.
     * Contoh: format(150000, 'IDR') → "Rp 150.000"
     *          format(10000, 'USD') → "$ 10.000"
     */
    public static function formatWithSymbol(int $amount, string $currency = 'IDR'): string
    {
        $symbol = self::symbol($currency);
        $formatted = self::format($amount);

        return $symbol.' '.$formatted;
    }

    /**
     * Simbol mata uang umum.
     */
    public static function symbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'IDR' => 'Rp',
            'USD' => '$',
            'EUR' => '€',
            'SGD' => 'S$',
            'MYR' => 'RM',
            'PHP' => '₱',
            'THB' => '฿',
            'VND' => '₫',
            default => strtoupper($currency),
        };
    }

    /**
     * Konversi nominal dari unit terkecil (cents/sen) ke unit penuh.
     * Contoh: 15000 cents → 150 (USD), 150000 sen → 150000 (IDR, karena IDR tidak pakai desimal).
     *
     * @param  int  $decimalPlaces  Jumlah digit desimal mata uang. IDR = 0, USD/EUR = 2.
     */
    public static function fromSmallestUnit(int $amount, int $decimalPlaces = 2): float
    {
        if ($decimalPlaces === 0) {
            return (float) $amount;
        }

        return $amount / (10 ** $decimalPlaces);
    }

    /**
     * Konversi dari unit penuh ke unit terkecil.
     */
    public static function toSmallestUnit(float $amount, int $decimalPlaces = 2): int
    {
        return (int) round($amount * (10 ** $decimalPlaces));
    }

    /**
     * Pastikan nominal adalah bilangan bulat positif.
     *
     * @throws \InvalidArgumentException
     */
    public static function assertPositive(int $amount, string $field = 'amount'): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                "PayID: field [{$field}] must be a positive integer, got [{$amount}].",
            );
        }
    }

    // -----------------------------------------------------------------------
    // Backward compatibility — IDR helpers
    // -----------------------------------------------------------------------

    /** @deprecated Use format() instead */
    public static function formatRupiah(int $amount): string
    {
        return self::format($amount);
    }

    /** @deprecated Use formatWithSymbol($amount, 'IDR') instead */
    public static function formatRupiahFull(int $amount): string
    {
        return self::formatWithSymbol($amount, 'IDR');
    }

    /** @deprecated Use fromSmallestUnit($amount, 2) instead */
    public static function fromCents(int $cents): int
    {
        return (int) round($cents / 100);
    }

    /** @deprecated Use toSmallestUnit($amount, 2) instead */
    public static function toCents(int $amount): int
    {
        return $amount * 100;
    }
}
