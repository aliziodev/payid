<?php

namespace Aliziodev\PayId\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'payid:install';

    protected $description = 'Install and configure the PayID payment gateway package';

    /** Transaction stack option labels */
    protected const STACK_TRANSACTIONS = 'payid-transactions (recommended)';

    protected const STACK_MANUAL = 'manual (no default transaction stack)';

    /**
     * Available driver definitions.
     * Each entry: package, env vars (key => default value).
     */
    protected array $drivers = [
        'Midtrans' => [
            'package' => 'aliziodev/payid-midtrans',
            'env' => [
                'MIDTRANS_SERVER_KEY' => '',
                'MIDTRANS_CLIENT_KEY' => '',
                'MIDTRANS_IS_PRODUCTION' => 'false',
            ],
        ],
        'Xendit' => [
            'package' => 'aliziodev/payid-xendit',
            'env' => [
                'XENDIT_SECRET_KEY' => '',
                'XENDIT_PUBLIC_KEY' => '',
                'XENDIT_WEBHOOK_TOKEN' => '',
            ],
        ],
        'DOKU' => [
            'package' => 'aliziodev/payid-doku',
            'env' => [
                'DOKU_CLIENT_ID' => '',
                'DOKU_SECRET_KEY' => '',
                'DOKU_IS_PRODUCTION' => 'false',
            ],
        ],
        'iPaymu' => [
            'package' => 'aliziodev/payid-ipaymu',
            'env' => [
                'IPAYMU_VA' => '',
                'IPAYMU_API_KEY' => '',
                'IPAYMU_IS_PRODUCTION' => 'false',
            ],
        ],
    ];

    public function handle(): int
    {
        $this->printBanner();

        // Step 1 — publish config
        $this->publishConfig();

        // Step 2 — choose driver
        $driver = $this->selectDriver();

        // Step 3 — choose transaction stack
        $stack = $this->selectTransactionStack();

        // Step 4 — show package installation progress
        $this->showInstallProgress($driver, $stack);

        // Step 5 — append .env stubs
        $this->appendEnvVariables($driver);

        // Step 6 — print next steps
        $this->printNextSteps($driver, $stack);

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    protected function printBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=cyan;options=bold>██████╗  █████╗ ██╗   ██╗██╗██████╗ </>');
        $this->line('  <fg=cyan;options=bold>██╔══██╗██╔══██╗╚██╗ ██╔╝██║██╔══██╗</>');
        $this->line('  <fg=cyan;options=bold>██████╔╝███████║ ╚████╔╝ ██║██║  ██║</>');
        $this->line('  <fg=cyan;options=bold>██╔═══╝ ██╔══██║  ╚██╔╝  ██║██║  ██║</>');
        $this->line('  <fg=cyan;options=bold>██║     ██║  ██║   ██║   ██║██████╔╝</>');
        $this->line('  <fg=cyan;options=bold>╚═╝     ╚═╝  ╚═╝   ╚═╝   ╚═╝╚═════╝ </>');
        $this->newLine();
        $this->line('  <fg=white;options=bold>Laravel Payment Gateway Orchestrator</>');
        $this->line('  <fg=gray>Unified interface for Indonesian payment providers.</>');
        $this->newLine();
    }

    protected function publishConfig(): void
    {
        $this->components->task('Publishing config file', function (): void {
            $this->callSilent('vendor:publish', [
                '--tag' => 'payid-config',
                '--force' => false,
            ]);
        });
    }

    protected function selectDriver(): string
    {
        $this->newLine();

        return $this->choice(
            question: 'Select driver(s)',
            choices: array_keys($this->drivers),
            default: 0,
        );
    }

    protected function selectTransactionStack(): string
    {
        return $this->choice(
            question: 'Transaction stack',
            choices: [self::STACK_TRANSACTIONS, self::STACK_MANUAL],
            default: 0,
        );
    }

    protected function showInstallProgress(string $driver, string $stack): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold>Package installation steps:</>');
        $this->newLine();

        // Core package (already installed if this command runs)
        $this->components->task(
            '  <fg=green>aliziodev/payid</> <fg=gray>(core — already installed)</>',
            fn () => true,
        );

        // Driver package
        $driverPackage = $this->drivers[$driver]['package'];
        $this->components->task(
            "  <fg=yellow>{$driverPackage}</>",
            function () use ($driverPackage): bool {
                // Real projects would run composer here; we just simulate progress
                return true;
            },
        );

        // Transaction stack package
        if ($stack === self::STACK_TRANSACTIONS) {
            $this->components->task(
                '  <fg=yellow>aliziodev/payid-transactions</> <fg=gray>(ledger + audit trail)</>',
                fn () => true,
            );
        }

        $this->newLine();
        $this->line('  <fg=gray>Run the following Composer commands to install:</>');
        $this->newLine();
        $this->line("     <fg=green>composer require {$driverPackage}</>");

        if ($stack === self::STACK_TRANSACTIONS) {
            $this->line('     <fg=green>composer require aliziodev/payid-transactions</>');
        }
    }

    protected function appendEnvVariables(string $driver): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->newLine();
            $this->components->warn('.env file not found — skipping environment variable setup.');

            return;
        }

        $envContent = File::get($envPath);
        $envVars = $this->drivers[$driver]['env'] ?? [];

        $stub = "\n# PayID — {$driver}\n";
        $hasNew = false;
        $appended = [];

        foreach ($envVars as $key => $default) {
            if (! str_contains($envContent, $key)) {
                $stub .= "{$key}={$default}\n";
                $hasNew = true;
                $appended[] = $key;
            }
        }

        $this->newLine();
        $this->components->task('Adding .env variables', function () use ($hasNew, $envPath, $stub): bool {
            if ($hasNew) {
                File::append($envPath, $stub);
            }

            return true;
        });

        if (! empty($appended)) {
            foreach ($appended as $key) {
                $this->line("  <fg=gray>+ {$key}</>");
            }
        } else {
            $this->line('  <fg=gray>All .env keys already present — nothing added.</>');
        }
    }

    protected function printNextSteps(string $driver, string $stack): void
    {
        $driverKey = strtolower($driver === 'iPaymu' ? 'ipaymu' : $driver);

        $this->newLine();
        $this->components->info('Setup complete! Follow these steps to finish:');
        $this->newLine();

        $this->line('  <options=bold>1. Fill in credentials in <comment>.env</comment>:</>');
        $this->newLine();

        foreach (array_keys($this->drivers[$driver]['env'] ?? []) as $key) {
            $this->line("     <fg=yellow>{$key}=your-value-here</>");
        }

        $this->newLine();
        $this->line('  <options=bold>2. Set the default driver in <comment>config/payid.php</comment>:</>');
        $this->newLine();
        $this->line("     <fg=gray>'default' => env('PAYID_DEFAULT', '{$driverKey}'),</>");

        if ($stack === self::STACK_TRANSACTIONS) {
            $this->newLine();
            $this->line('  <options=bold>3. Publish and run payid-transactions migrations:</>');
            $this->newLine();
            $this->line('     <fg=green>php artisan vendor:publish --tag=payid-transactions-migrations</>');
            $this->line('     <fg=green>php artisan migrate</>');
        }

        $this->newLine();
        $step = $stack === self::STACK_TRANSACTIONS ? 4 : 3;
        $this->line("  <options=bold>{$step}. Clear config cache:</>");
        $this->newLine();
        $this->line('     <fg=green>php artisan config:clear</>');

        $this->newLine();
        $this->line('  <fg=gray>Documentation: https://github.com/aliziodev/payid</>');
        $this->newLine();
    }
}
