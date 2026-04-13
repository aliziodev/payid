<?php

namespace Aliziodev\PayId\Enums;

enum PaymentStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    /**
     * Status terminal adalah status di mana transaksi tidak akan berubah lagi.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Failed,
            self::Expired,
            self::Cancelled,
            self::Refunded,
        ], true);
    }

    /**
     * Transaksi dianggap berhasil hanya jika status Paid.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Transaksi masih bisa menerima pembayaran.
     */
    public function isAwaitingPayment(): bool
    {
        return in_array($this, [
            self::Created,
            self::Pending,
            self::Authorized,
        ], true);
    }

    /**
     * Transaksi sudah dikembalikan (penuh atau sebagian).
     */
    public function isRefunded(): bool
    {
        return in_array($this, [
            self::Refunded,
            self::PartiallyRefunded,
        ], true);
    }
}
