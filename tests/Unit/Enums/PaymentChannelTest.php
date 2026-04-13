<?php

use Aliziodev\PayId\Enums\PaymentChannel;

describe('PaymentChannel', function () {

    it('can be created from string value', function () {
        expect(PaymentChannel::from('qris'))->toBe(PaymentChannel::Qris);
        expect(PaymentChannel::from('va_bca'))->toBe(PaymentChannel::VaBca);
        expect(PaymentChannel::from('gopay'))->toBe(PaymentChannel::Gopay);
    });

    it('identifies virtual account channels correctly', function () {
        expect(PaymentChannel::VaBca->isVirtualAccount())->toBeTrue();
        expect(PaymentChannel::VaBni->isVirtualAccount())->toBeTrue();
        expect(PaymentChannel::VaBri->isVirtualAccount())->toBeTrue();
        expect(PaymentChannel::VaMandiri->isVirtualAccount())->toBeTrue();

        expect(PaymentChannel::Qris->isVirtualAccount())->toBeFalse();
        expect(PaymentChannel::Gopay->isVirtualAccount())->toBeFalse();
    });

    it('identifies e-wallet channels correctly', function () {
        expect(PaymentChannel::Gopay->isEWallet())->toBeTrue();
        expect(PaymentChannel::Shopeepay->isEWallet())->toBeTrue();
        expect(PaymentChannel::Ovo->isEWallet())->toBeTrue();
        expect(PaymentChannel::Dana->isEWallet())->toBeTrue();

        expect(PaymentChannel::VaBca->isEWallet())->toBeFalse();
        expect(PaymentChannel::Qris->isEWallet())->toBeFalse();
    });

    it('returns correct labels', function () {
        expect(PaymentChannel::Qris->label())->toBe('QRIS');
        expect(PaymentChannel::VaBca->label())->toBe('Virtual Account BCA');
        expect(PaymentChannel::Gopay->label())->toBe('GoPay');
        expect(PaymentChannel::CreditCard->label())->toBe('Kartu Kredit');
    });

});
