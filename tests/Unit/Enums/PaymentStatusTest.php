<?php

use Aliziodev\PayId\Enums\PaymentStatus;

describe('PaymentStatus', function () {

    it('can be created from string value', function () {
        expect(PaymentStatus::from('paid'))->toBe(PaymentStatus::Paid);
        expect(PaymentStatus::from('pending'))->toBe(PaymentStatus::Pending);
        expect(PaymentStatus::from('failed'))->toBe(PaymentStatus::Failed);
    });

    it('identifies terminal statuses correctly', function () {
        expect(PaymentStatus::Paid->isTerminal())->toBeTrue();
        expect(PaymentStatus::Failed->isTerminal())->toBeTrue();
        expect(PaymentStatus::Expired->isTerminal())->toBeTrue();
        expect(PaymentStatus::Cancelled->isTerminal())->toBeTrue();
        expect(PaymentStatus::Refunded->isTerminal())->toBeTrue();

        expect(PaymentStatus::Pending->isTerminal())->toBeFalse();
        expect(PaymentStatus::Created->isTerminal())->toBeFalse();
        expect(PaymentStatus::Authorized->isTerminal())->toBeFalse();
    });

    it('identifies successful status correctly', function () {
        expect(PaymentStatus::Paid->isSuccessful())->toBeTrue();

        foreach (PaymentStatus::cases() as $status) {
            if ($status !== PaymentStatus::Paid) {
                expect($status->isSuccessful())->toBeFalse();
            }
        }
    });

    it('identifies awaiting payment statuses correctly', function () {
        expect(PaymentStatus::Created->isAwaitingPayment())->toBeTrue();
        expect(PaymentStatus::Pending->isAwaitingPayment())->toBeTrue();
        expect(PaymentStatus::Authorized->isAwaitingPayment())->toBeTrue();

        expect(PaymentStatus::Paid->isAwaitingPayment())->toBeFalse();
        expect(PaymentStatus::Failed->isAwaitingPayment())->toBeFalse();
    });

    it('identifies refunded statuses correctly', function () {
        expect(PaymentStatus::Refunded->isRefunded())->toBeTrue();
        expect(PaymentStatus::PartiallyRefunded->isRefunded())->toBeTrue();

        expect(PaymentStatus::Paid->isRefunded())->toBeFalse();
        expect(PaymentStatus::Cancelled->isRefunded())->toBeFalse();
    });

});
