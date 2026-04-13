<?php

namespace Aliziodev\PayId\Support;

class Mask
{
    /**
     * Mask API key / secret key: tampilkan beberapa karakter pertama saja.
     * Contoh: "SB-Mid-server-abc123xyz" → "SB-Mi****"
     */
    public static function apiKey(string $key, int $visibleChars = 5): string
    {
        if (strlen($key) <= $visibleChars) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, $visibleChars).'****';
    }

    /**
     * Mask nomor kartu kredit: tampilkan 4 digit terakhir.
     * Contoh: "4111111111111111" → "****-****-****-1111"
     */
    public static function cardNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);
        $last4 = substr($number, -4);

        return '****-****-****-'.$last4;
    }

    /**
     * Mask alamat email: tampilkan 1 karakter pertama dan domain.
     *
     * Contoh: "budi@example.com" → "b***@example.com"
     */
    public static function email(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return substr($local, 0, 1).'***@'.$domain;
    }

    /**
     * Mask nomor telepon: tampilkan 4 digit pertama dan 2 digit terakhir.
     * Contoh: "08123456789" → "0812***89"
     */
    public static function phone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $length = strlen($digits);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return substr($digits, 0, 4).'***'.substr($digits, -2);
    }

    /**
     * Mask field-field sensitif dari array sebelum dilog.
     * Field yang tidak ada di array $sensitiveKeys akan dibiarkan.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $sensitiveKeys
     * @return array<string, mixed>
     */
    public static function array(array $data, array $sensitiveKeys = []): array
    {
        $defaults = [
            'server_key', 'secret_key', 'api_key', 'private_key',
            'client_key', 'public_key', 'webhook_token', 'signature',
            'password', 'token', 'access_token', 'refresh_token',
        ];

        $keys = array_unique(array_merge($defaults, $sensitiveKeys));

        $result = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $keys, true)) {
                $result[$key] = is_string($value) ? self::apiKey($value) : '****';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
