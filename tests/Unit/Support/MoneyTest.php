<?php

use Aliziodev\PayId\Support\Money;

describe('Money', function () {

    it('formats rupiah without symbol', function () {
        expect(Money::formatRupiah(150000))->toBe('150.000');
        expect(Money::formatRupiah(1000000))->toBe('1.000.000');
        expect(Money::formatRupiah(500))->toBe('500');
    });

    it('formats rupiah with symbol', function () {
        expect(Money::formatRupiahFull(150000))->toBe('Rp 150.000');
    });

    it('converts from cents to rupiah', function () {
        expect(Money::fromCents(15000000))->toBe(150000);
    });

    it('converts from rupiah to cents', function () {
        expect(Money::toCents(150000))->toBe(15000000);
    });

    it('throws exception for non-positive amount', function () {
        expect(fn () => Money::assertPositive(0))->toThrow(InvalidArgumentException::class);
        expect(fn () => Money::assertPositive(-100))->toThrow(InvalidArgumentException::class);
    });

    it('does not throw for positive amount', function () {
        Money::assertPositive(1);
        Money::assertPositive(150000);
        expect(true)->toBeTrue();
    });

});
