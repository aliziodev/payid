<?php

namespace Aliziodev\PayId\Enums;

enum PaymentChannel: string
{
    // Virtual Account
    case VaBca = 'va_bca';
    case VaBni = 'va_bni';
    case VaBri = 'va_bri';
    case VaMandiri = 'va_mandiri';
    case VaPermata = 'va_permata';
    case VaCimb = 'va_cimb';
    case VaOther = 'va_other';

    // QRIS
    case Qris = 'qris';

    // E-Wallet
    case Gopay = 'gopay';
    case Shopeepay = 'shopeepay';
    case Ovo = 'ovo';
    case Dana = 'dana';
    case Linkaja = 'linkaja';
    case Sakuku = 'sakuku';

    // Kartu
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';

    // Convenience Store
    case CstoreAlfamart = 'cstore_alfamart';
    case CstoreIndomaret = 'cstore_indomaret';

    // Transfer Bank
    case BankTransfer = 'bank_transfer';

    // Invoice / Payment Link
    case PaymentLink = 'payment_link';
    case Invoice = 'invoice';

    public function isVirtualAccount(): bool
    {
        return str_starts_with($this->value, 'va_');
    }

    public function isEWallet(): bool
    {
        return in_array($this, [
            self::Gopay,
            self::Shopeepay,
            self::Ovo,
            self::Dana,
            self::Linkaja,
            self::Sakuku,
        ], true);
    }

    public function isCard(): bool
    {
        return in_array($this, [
            self::CreditCard,
            self::DebitCard,
        ], true);
    }

    public function isConvenienceStore(): bool
    {
        return in_array($this, [
            self::CstoreAlfamart,
            self::CstoreIndomaret,
        ], true);
    }

    /**
     * Label tampilan dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::VaBca => 'Virtual Account BCA',
            self::VaBni => 'Virtual Account BNI',
            self::VaBri => 'Virtual Account BRI',
            self::VaMandiri => 'Virtual Account Mandiri',
            self::VaPermata => 'Virtual Account Permata',
            self::VaCimb => 'Virtual Account CIMB',
            self::VaOther => 'Virtual Account',
            self::Qris => 'QRIS',
            self::Gopay => 'GoPay',
            self::Shopeepay => 'ShopeePay',
            self::Ovo => 'OVO',
            self::Dana => 'DANA',
            self::Linkaja => 'LinkAja',
            self::Sakuku => 'Sakuku',
            self::CreditCard => 'Kartu Kredit',
            self::DebitCard => 'Kartu Debit',
            self::CstoreAlfamart => 'Alfamart',
            self::CstoreIndomaret => 'Indomaret',
            self::BankTransfer => 'Transfer Bank',
            self::PaymentLink => 'Payment Link',
            self::Invoice => 'Invoice',
        };
    }
}
