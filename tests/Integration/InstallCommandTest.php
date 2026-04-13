<?php

$allDrivers = ['Midtrans', 'Xendit', 'iPaymu'];
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

    it('shows correct composer command for iPaymu', function () use ($allDrivers, $stackChoices) {
        $this->artisan('payid:install', ['--no-install' => true])
            ->expectsChoice('Select driver(s)', 'iPaymu', $allDrivers)
            ->expectsChoice('Transaction stack', $stackChoices[1], $stackChoices)
            ->expectsOutputToContain('composer require aliziodev/payid-ipaymu')
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
