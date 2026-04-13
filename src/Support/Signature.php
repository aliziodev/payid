<?php

namespace Aliziodev\PayId\Support;

class Signature
{
    /**
     * Buat HMAC-SHA256 signature.
     */
    public static function hmacSha256(string $data, string $secret): string
    {
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Buat HMAC-SHA512 signature.
     */
    public static function hmacSha512(string $data, string $secret): string
    {
        return hash_hmac('sha512', $data, $secret);
    }

    /**
     * Buat SHA512 hash dari string.
     */
    public static function sha512(string $data): string
    {
        return hash('sha512', $data);
    }

    /**
     * Buat SHA256 hash dari string.
     */
    public static function sha256(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Bandingkan dua signature secara timing-safe untuk mencegah timing attack.
     */
    public static function timingSafeEquals(string $known, string $provided): bool
    {
        return hash_equals($known, $provided);
    }

    /**
     * Encode string ke Base64.
     */
    public static function base64Encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * Decode string dari Base64.
     */
    public static function base64Decode(string $data): string|false
    {
        return base64_decode($data, true);
    }
}
