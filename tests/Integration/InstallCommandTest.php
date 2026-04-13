<?php

$allDrivers = ['Midtrans', 'Xendit', 'DOKU', 'iPaymu', 'Nicepay', 'OY! Indonesia', 'Tripay'];
$stackChoices = ['payid-transactions (recommended)', 'manual (no default transaction stack)'];

describe('InstallCommand', function () use ($allDrivers, $stackChoices) {

    it('runs successfully — Midtrans + payid-transactions stack', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'Midtrans', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[0], $stackChoices)
            ->assertExitCode(0);
    });

    it('runs successfully — Midtrans + manual stack', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'Midtrans', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[1], $stackChoices)
            ->assertExitCode(0);
    });

    it('shows correct composer command for Xendit', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'Xendit', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[1], $stackChoices)
            ->expectsOutputToContain('composer require aliziodev/payid-xendit')
            ->assertExitCode(0);
    });

    it('shows correct composer command for DOKU', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'DOKU', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[1], $stackChoices)
            ->expectsOutputToContain('composer require aliziodev/payid-doku')
            ->assertExitCode(0);
    });

    it('shows correct composer command for Nicepay', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'Nicepay', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[1], $stackChoices)
            ->expectsOutputToContain('composer require aliziodev/payid-nicepay')
            ->assertExitCode(0);
    });

    it('shows correct composer command for OY! Indonesia', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'OY! Indonesia', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[1], $stackChoices)
            ->expectsOutputToContain('composer require aliziodev/payid-oyid')
            ->assertExitCode(0);
    });

    it('shows correct composer command for Tripay', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'Tripay', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[1], $stackChoices)
            ->expectsOutputToContain('composer require aliziodev/payid-tripay')
            ->assertExitCode(0);
    });

    it('shows payid-transactions migration commands when stack selected', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'Midtrans', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[0], $stackChoices)
            ->expectsOutputToContain('payid-transactions-migrations')
            ->assertExitCode(0);
    });

});
