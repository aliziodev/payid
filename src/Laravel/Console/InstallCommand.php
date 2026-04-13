<?php

namespace Aliziodev\PayId\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'payid:install';

    protected $description = 'Install and configure the PayID payment gateway package';

    /**
     * Available driver definitions.
     * Each entry: label, package, env vars (key => example value).
     *
     * @var array<string, array{package: string, env: array<string, string>}>
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

        // Step 2 — select drivers
        $selected = $this->selectDrivers();

        if (empty($selected)) {
            $this->components->warn('No drivers selected. You can run <comment>payid:install</comment> again at any time.');

            return self::SUCCESS;
        }

        // Step 3 — select transaction stack
        $transactionStack = $this->selectTransactionStack();

        // Step 4 — append .env stubs
        $this->appendEnvVariables($selected);

        // Step 5 — print install instructions
        $this->printInstallInstructions($selected, $transactionStack);

        return self::SUCCESS;
    }

    // -----------------------------------------------------------------------

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
        $this->components->task('Publishing config file', function () {
            $this->callSilent('vendor:publish', [
                '--tag' => 'payid-config',
                '--force' => false,
            ]);
        });
    }

    /**
     * @return string[] Selected driver labels (e.g. ['Midtrans', 'DOKU'])
     */
    protected function selectDrivers(): array
    {
        $this->newLine();
        $this->components->info('Which payment gateway driver(s) do you want to configure?');
        $this->line('  <fg=gray>Use spacebar to select, Enter to confirm.</>');
        $this->newLine();

        $labels = array_keys($this->drivers);

        $selected = $this->choice(
            question: 'Select driver(s)',
            choices: $labels,
            default: null,
            attempts: null,
            multiple: true,
        );

        // choice() may return a string when only one is selected
        return (array) $selected;
    }

    protected function selectTransactionStack(): string
    {
        $this->newLine();
        $this->components->info('Select transaction storage stack:');

        return $this->choice(
            question: 'Transaction stack',
            choices: [
                'payid-transactions (recommended)',
                'manual (no default transaction stack)',
            ],
            default: 'payid-transactions (recommended)',
        );
    }

    /**
     * Append missing .env keys for selected drivers.
     *
     * @param  string[]  $selected
     */
    protected function appendEnvVariables(array $selected): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->components->warn('.env file not found — skipping environment variable setup.');

            return;
        }

        $envContent = File::get($envPath);
        $appended = [];

        foreach ($selected as $label) {
            $envVars = $this->drivers[$label]['env'] ?? [];

            $stub = "\n# PayID — {$label}\n";
            $hasNew = false;

            foreach ($envVars as $key => $default) {
                if (! str_contains($envContent, $key)) {
                    $stub .= "{$key}={$default}\n";
                    $hasNew = true;
                    $appended[] = $key;
                }
            }

            if ($hasNew) {
                File::append($envPath, $stub);
            }
        }

        $this->components->task('Adding .env variables', function () {
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

    /**
     * @param  string[]  $selected
     */
    protected function printInstallInstructions(array $selected, string $transactionStack): void
    {
        $this->newLine();
        $this->components->info('Installation complete!');
        $this->newLine();

        // Composer require commands
        $this->line('  <options=bold>1. Install driver package(s) via Composer:</>');
        $this->newLine();

        foreach ($selected as $label) {
            $package = $this->drivers[$label]['package'];
            $this->line("     <fg=green>composer require {$package}</>");
        }

        if ($transactionStack === 'payid-transactions (recommended)') {
            $this->line('     <fg=green>composer require aliziodev/payid-transactions</>');
        }

        $this->newLine();

        // .env fill-in reminder
        $this->line('  <options=bold>2. Fill in your credentials in <comment>.env</comment>:</>');
        $this->newLine();

        foreach ($selected as $label) {
            $envVars = array_keys($this->drivers[$label]['env'] ?? []);
            if (! empty($envVars)) {
                $this->line("     <fg=yellow>{$label}:</> ".implode(', ', $envVars));
            }
        }

        $this->newLine();

        // Config driver setup
        $this->line('  <options=bold>3. Set the default driver in <comment>config/payid.php</comment>:</>');
        $this->newLine();

        $defaultDriver = strtolower(reset($selected));
        $this->line("     <fg=gray>'default' => env('PAYID_DEFAULT_DRIVER', '{$defaultDriver}'),</>");

        $this->newLine();

        // Clear cache
        $this->line('  <options=bold>4. Clear config cache:</>');
        $this->newLine();
        $this->line('     <fg=green>php artisan config:clear</>');

        $this->newLine();
        $this->line('  <options=bold>5. Transaction stack next step:</>');
        $this->newLine();

        if ($transactionStack === 'payid-transactions (recommended)') {
            $this->line('     <fg=gray>You selected payid-transactions.</>');
            $this->line('     <fg=green>php artisan vendor:publish --tag=payid-transactions-migrations</>');
            $this->line('     <fg=green>php artisan migrate</>');
        } else {
            $this->line('     <fg=gray>You selected manual storage (no default transaction stack).</>');
            $this->line('     <fg=gray>Implement your own persistence layer or install payid-transactions later.</>');
        }

        $this->newLine();
        $this->line('  <fg=gray>Documentation: https://github.com/aliziodev/payid</>');
        $this->newLine();
    }
}
