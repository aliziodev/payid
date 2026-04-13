<?php

use Aliziodev\PayId\Support\Mask;

describe('Mask', function () {

    describe('apiKey()', function () {
        it('masks an API key showing first 5 characters', function () {
            $result = Mask::apiKey('SB-Mid-server-abc123xyz');
            expect($result)->toBe('SB-Mi****');
        });

        it('masks a short key entirely', function () {
            $result = Mask::apiKey('abc', 5);
            expect($result)->toBe('***');
        });
    });

    describe('email()', function () {
        it('masks email correctly', function () {
            expect(Mask::email('budi@example.com'))->toBe('b***@example.com');
            expect(Mask::email('a@test.id'))->toBe('a***@test.id');
        });
    });

    describe('cardNumber()', function () {
        it('masks card number showing last 4 digits', function () {
            expect(Mask::cardNumber('4111111111111111'))->toBe('****-****-****-1111');
            expect(Mask::cardNumber('5500000000000004'))->toBe('****-****-****-0004');
        });
    });

    describe('phone()', function () {
        it('masks phone number', function () {
            $result = Mask::phone('08123456789');
            expect($result)->toBe('0812***89');
        });
    });

    describe('array()', function () {
        it('masks default sensitive fields', function () {
            $data = [
                'server_key' => 'SB-Mid-server-abc123',
                'client_key' => 'SB-Mid-client-xyz789',
                'amount' => 150000,
                'order_id' => 'ORDER-001',
            ];

            $masked = Mask::array($data);

            expect($masked['amount'])->toBe(150000);
            expect($masked['order_id'])->toBe('ORDER-001');
            expect($masked['server_key'])->not->toBe('SB-Mid-server-abc123');
            expect($masked['client_key'])->not->toBe('SB-Mid-client-xyz789');
        });

        it('masks custom sensitive fields', function () {
            $data = ['my_secret' => 'super-secret-value', 'name' => 'Budi'];
            $masked = Mask::array($data, ['my_secret']);

            expect($masked['my_secret'])->not->toBe('super-secret-value');
            expect($masked['name'])->toBe('Budi');
        });
    });

});
