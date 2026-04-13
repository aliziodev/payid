<?php

namespace Aliziodev\PayId;

use Aliziodev\PayId\Console\InstallCommand;
use Aliziodev\PayId\Factories\DriverFactory;
use Aliziodev\PayId\Managers\PayIdManager;
use Aliziodev\PayId\Webhooks\WebhookProcessor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PayIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/payid.php',
            'payid',
        );

        $this->registerDriverFactory();
        $this->registerManager();
        $this->registerWebhookProcessor();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/payid.php' => config_path('payid.php'),
        ], 'payid-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    protected function registerDriverFactory(): void
    {
        $this->app->singleton(DriverFactory::class, function (Container $app): DriverFactory {
            return new DriverFactory($app);
        });
    }

    protected function registerManager(): void
    {
        $this->app->singleton(PayIdManager::class, function (Container $app): PayIdManager {
            return new PayIdManager(
                config: $app['config'],
                factory: $app->make(DriverFactory::class),
                events: $app['events'],
                ledger: $app->bound('payid-transactions.ledger')
                    ? $app->make('payid-transactions.ledger')
                    : null,
            );
        });

        $this->app->alias(PayIdManager::class, 'payid');
    }

    protected function registerWebhookProcessor(): void
    {
        $this->app->singleton(WebhookProcessor::class, function (Container $app): WebhookProcessor {
            return new WebhookProcessor(
                manager: $app->make(PayIdManager::class),
                events: $app['events'],
                logger: Log::channel($app['config']->get('payid.logging.channel')),
                ledger: $app->bound('payid-transactions.ledger')
                    ? $app->make('payid-transactions.ledger')
                    : null,
            );
        });
    }

    public function provides(): array
    {
        return [
            PayIdManager::class,
            DriverFactory::class,
            WebhookProcessor::class,
            InstallCommand::class,
            'payid',
        ];
    }
}
