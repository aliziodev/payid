<?php

use Aliziodev\PayId\Support\Signature;

describe('Signature', function () {

    it('generates HMAC-SHA256', function () {
        $result = Signature::hmacSha256('my-data', 'my-secret');
        expect($result)->toBe(hash_hmac('sha256', 'my-data', 'my-secret'));
    });

    it('generates HMAC-SHA512', function () {
        $result = Signature::hmacSha512('my-data', 'my-secret');
        expect($result)->toBe(hash_hmac('sha512', 'my-data', 'my-secret'));
    });

    it('compares signatures in timing-safe manner', function () {
        $sig = Signature::hmacSha256('data', 'secret');

        expect(Signature::timingSafeEquals($sig, $sig))->toBeTrue();
        expect(Signature::timingSafeEquals($sig, 'wrong-signature'))->toBeFalse();
    });

    it('encodes and decodes Base64', function () {
        $original = 'hello-payid-123';
        $encoded = Signature::base64Encode($original);
        $decoded = Signature::base64Decode($encoded);

        expect($decoded)->toBe($original);
    });

    it('generates SHA256 hash', function () {
        $result = Signature::sha256('my-data');
        expect($result)->toBe(hash('sha256', 'my-data'));
    });

});
