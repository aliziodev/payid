<?php

describe('InstallCommand', function () {

    it('runs successfully when selecting a single driver with payid-transactions stack', function () {
        $this->artisan('payid:install')
            ->expectsChoice('Select driver(s)', 'Midtrans', ['Midtrans', 'Xendit', 'DOKU', 'iPaymu'])
            ->expectsChoice('Transaction stack', 'payid-transactions (recommended)', ['payid-transactions (recommended)', 'manual (no default transaction stack)'])
            ->assertExitCode(0);
    });

    it('runs successfully when selecting manual transaction stack', function () {
        $this->artisan('payid:install')
            ->expectsChoice('Select driver(s)', 'Midtrans', ['Midtrans', 'Xendit', 'DOKU', 'iPaymu'])
            ->expectsChoice('Transaction stack', 'manual (no default transaction stack)', ['payid-transactions (recommended)', 'manual (no default transaction stack)'])
            ->assertExitCode(0);
    });
});
