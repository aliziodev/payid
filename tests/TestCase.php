<?php

namespace Aliziodev\PayId\Tests;

use Aliziodev\PayId\Facades\PayId;
use Aliziodev\PayId\PayIdServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PayIdServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'PayId' => PayId::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('payid.default', 'fake');
        $app['config']->set('payid.drivers.fake', ['driver' => 'fake']);
    }
}
