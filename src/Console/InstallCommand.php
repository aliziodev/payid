<?php

namespace Aliziodev\PayId\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    protected $signature = 'payid:install
                            {--no-install : Skip running composer require (show commands to run manually)}';

    protected $description = 'Install and configure the PayID payment gateway package';

    protected const STACK_TRANSACTIONS = 'payid-transactions (recommended)';

    protected const STACK_MANUAL = 'manual (no default transaction stack)';

    /**
     * Driver registry — harus selaras dengan config/payid.php drivers[].
     *
     * Setiap entry:
     *   config_key  — key di config('payid.drivers.*')
     *   package     — nama Composer package driver
     *   env         — pasangan ENV_KEY => default_value yang akan ditambahkan ke .env
     *                 (urutan dan nama HARUS sama dengan yang di config/payid.php)
     */
    protected array $drivers = [

        // ---------------------------------------------------------------
        // Midtrans — config('payid.drivers.midtrans')
        // ---------------------------------------------------------------
        'Midtrans' => [
            'config_key' => 'midtrans',
            'package' => 'aliziodev/payid-midtrans',
            'env' => [
                'PAYID_DEFAULT_DRIVER' => 'midtrans',
                'MIDTRANS_ENV' => 'sandbox',
                'MIDTRANS_SERVER_KEY' => '',
                'MIDTRANS_CLIENT_KEY' => '',
                'MIDTRANS_MERCHANT_ID' => '',
                'MIDTRANS_ORDER_PREFIX' => '',
            ],
        ],

        // ---------------------------------------------------------------
        // Xendit — config('payid.drivers.xendit')
        // ---------------------------------------------------------------
        'Xendit' => [
            'config_key' => 'xendit',
            'package' => 'aliziodev/payid-xendit',
            'env' => [
                'PAYID_DEFAULT_DRIVER' => 'xendit',
                'XENDIT_ENV' => 'test',
                'XENDIT_SECRET_KEY' => '',
                'XENDIT_PUBLIC_KEY' => '',
                'XENDIT_WEBHOOK_TOKEN' => '',
            ],
        ],

        // ---------------------------------------------------------------
        // DOKU — config('payid.drivers.doku')
        // ---------------------------------------------------------------
        'DOKU' => [
            'config_key' => 'doku',
            'package' => 'aliziodev/payid-doku',
            'env' => [
                'PAYID_DEFAULT_DRIVER' => 'doku',
                'DOKU_ENV' => 'sandbox',
                'DOKU_CLIENT_ID' => '',
                'DOKU_SECRET_KEY' => '',
                'DOKU_SHARED_KEY' => '',
            ],
        ],

        // ---------------------------------------------------------------
        // iPaymu — config('payid.drivers.ipaymu')
        // ---------------------------------------------------------------
        'iPaymu' => [
            'config_key' => 'ipaymu',
            'package' => 'aliziodev/payid-ipaymu',
            'env' => [
                'PAYID_DEFAULT_DRIVER' => 'ipaymu',
                'IPAYMU_ENV' => 'sandbox',
                'IPAYMU_VA' => '',
                'IPAYMU_API_KEY' => '',
            ],
        ],

        // ---------------------------------------------------------------
        // Nicepay — config('payid.drivers.nicepay')
        // ---------------------------------------------------------------
        'Nicepay' => [
            'config_key' => 'nicepay',
            'package' => 'aliziodev/payid-nicepay',
            'env' => [
                'PAYID_DEFAULT_DRIVER' => 'nicepay',
                'NICEPAY_ENV' => 'sandbox',
                'NICEPAY_MERCHANT_ID' => '',
                'NICEPAY_MERCHANT_KEY' => '',
            ],
        ],

        // ---------------------------------------------------------------
        // OY! Indonesia — config('payid.drivers.oyid')
        // ---------------------------------------------------------------
        'OY! Indonesia' => [
            'config_key' => 'oyid',
            'package' => 'aliziodev/payid-oyid',
            'env' => [
                'PAYID_DEFAULT_DRIVER' => 'oyid',
                'OYID_ENV' => 'staging',
                'OYID_USERNAME' => '',
                'OYID_API_KEY' => '',
            ],
        ],

        // ---------------------------------------------------------------
        // Tripay — config('payid.drivers.tripay')
        // ---------------------------------------------------------------
        'Tripay' => [
            'config_key' => 'tripay',
            'package' => 'aliziodev/payid-tripay',
            'env' => [
                'PAYID_DEFAULT_DRIVER' => 'tripay',
                'TRIPAY_ENV' => 'sandbox',
                'TRIPAY_API_KEY' => '',
                'TRIPAY_PRIVATE_KEY' => '',
                'TRIPAY_MERCHANT_CODE' => '',
            ],
        ],

    ];

    public function handle(): int
    {
        $this->printBanner();

        $this->publishConfig();

        $driver = $this->selectDriver();
        $stack = $this->selectTransactionStack();
        $configKey = $this->drivers[$driver]['config_key'];

        $this->installPackages($driver, $stack);

        $this->appendEnvVariables($driver, $configKey);

        $this->printNextSteps($driver, $stack, $configKey);

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Banner & prompts
    // -------------------------------------------------------------------------

    protected function printBanner(): void
    {
        $this->newLine();

        // ── Merah (bright → normal) ───────────────────────────────────────────
        $this->line('  <fg=red;options=bold>██████╗  █████╗ ██╗   ██╗██╗██████╗ </>');
        $this->line('  <fg=red;options=bold>██╔══██╗██╔══██╗╚██╗ ██╔╝██║██╔══██╗</>');
        $this->line('  <fg=red;options=bold>██████╔╝███████║ ╚████╔╝ ██║██║  ██║</>');
        // ── Putih (normal → bright) ───────────────────────────────────────────
        $this->line('  <fg=white>██╔═══╝ ██╔══██║  ╚██╔╝  ██║██║  ██║</>');
        $this->line('  <fg=white;options=bold>██║     ██║  ██║   ██║   ██║██████╔╝</>');
        $this->line('  <fg=white;options=bold>╚═╝     ╚═╝  ╚═╝   ╚═╝   ╚═╝╚═════╝ </>');

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

    // -------------------------------------------------------------------------
    // Package installation
    // -------------------------------------------------------------------------

    protected function installPackages(string $driver, string $stack): void
    {
        $driverPackage = $this->drivers[$driver]['package'];
        $withTransactions = $stack === self::STACK_TRANSACTIONS;
        $skipActualInstall = (bool) $this->option('no-install');

        $this->newLine();
        $this->line('  <fg=white;options=bold>Installing packages:</>');
        $this->newLine();

        // Core — always already present
        $this->components->task(
            '<fg=green>aliziodev/payid</> <fg=gray>(core — already installed)</>',
            fn () => true,
        );

        if ($skipActualInstall) {
            $this->newLine();
            $this->line('  <fg=gray>Run the following commands to install:</>');
            $this->newLine();
            $this->line("     <fg=green>composer require {$driverPackage}</>");

            if ($withTransactions) {
                $this->line('     <fg=green>composer require aliziodev/payid-transactions</>');
            }

            return;
        }

        // Actually run composer
        $driverOk = $this->composerRequire($driverPackage);
        $transactionsOk = true;

        if ($withTransactions) {
            $transactionsOk = $this->composerRequire('aliziodev/payid-transactions');
        }

        // If anything failed, remind them of the manual commands
        if (! $driverOk || ! $transactionsOk) {
            $this->newLine();
            $this->components->warn('Some packages could not be installed automatically. Run these commands manually:');
            $this->newLine();

            if (! $driverOk) {
                $this->line("     <fg=green>composer require {$driverPackage}</>");
            }

            if (! $transactionsOk) {
                $this->line('     <fg=green>composer require aliziodev/payid-transactions</>');
            }
        }
    }

    /**
     * Run `composer require <package>`, stream only relevant progress lines,
     * then render a timed result line that mirrors components->task() style.
     */
    protected function composerRequire(string $package): bool
    {
        $this->newLine();
        $this->line("  <fg=cyan;options=bold>» composer require {$package}</>");
        $this->newLine();

        $command = $this->buildComposerCommand(['require', $package]);
        $process = new Process($command, base_path(), null, null, 300);

        $start = microtime(true);
        $hasOutput = false;

        $process->run(function (string $type, string $data) use (&$hasOutput): void {
            foreach (explode("\n", $data) as $line) {
                if ($this->isComposerProgressLine($line)) {
                    $this->line('  <fg=gray>'.rtrim($line).'</>');
                    $hasOutput = true;
                }
            }
        });

        $elapsed = microtime(true) - $start;
        $success = $process->isSuccessful();

        if ($hasOutput) {
            $this->newLine();
        }

        $this->renderInstallResult($package, $elapsed, $success);

        return $success;
    }

    /**
     * Only pass through lines that show actual package operations,
     * e.g. "  - Downloading …" / "  - Installing …" / "  - Upgrading …".
     */
    protected function isComposerProgressLine(string $line): bool
    {
        // Composer indents these with two spaces then "- "
        return (bool) preg_match('/^  - /', $line);
    }

    /**
     * Render a result line that visually matches components->task():
     *   package-name installed .............. 8.4s DONE
     */
    protected function renderInstallResult(string $package, float $elapsed, bool $success): void
    {
        $elapsedStr = $elapsed >= 1
            ? number_format($elapsed, 1).'s'
            : number_format($elapsed * 1000, 0).'ms';

        $statusText = $success ? 'installed' : 'installation failed';
        $statusColor = $success ? 'green' : 'red';
        $doneText = $success ? 'DONE' : 'FAIL';

        // Keep total visual width ~72 chars (matches components->task() default)
        $visibleLabel = "  {$package} {$statusText}";
        $visibleSuffix = " {$elapsedStr} {$doneText}";
        $dotsCount = max(3, 72 - mb_strlen($visibleLabel) - mb_strlen($visibleSuffix));

        $this->line(
            "  <fg={$statusColor}>{$package}</> {$statusText} ".
            '<fg=gray>'.str_repeat('.', $dotsCount)." {$elapsedStr}</> ".
            "<fg={$statusColor};options=bold>{$doneText}</>",
        );
    }

    /**
     * Build the composer command array, preferring a local composer.phar if present.
     *
     * @param  string[]  $args
     * @return string[]
     */
    protected function buildComposerCommand(array $args): array
    {
        $composerPhar = base_path('composer.phar');

        $binary = file_exists($composerPhar)
            ? [PHP_BINARY, $composerPhar]
            : ['composer'];

        return array_merge($binary, $args);
    }

    // -------------------------------------------------------------------------
    // .env stubs
    // -------------------------------------------------------------------------

    protected function appendEnvVariables(string $driver, string $configKey): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->newLine();
            $this->components->warn('.env file not found — skipping environment variable setup.');

            return;
        }

        $envContent = File::get($envPath);

        // Build env vars: swap the generic PAYID_DEFAULT_DRIVER placeholder for real driver value
        $envVars = $this->drivers[$driver]['env'] ?? [];

        $stub = "\n# PayID — {$driver}\n";
        $hasNew = false;
        $appended = [];
        $updated = [];

        foreach ($envVars as $key => $default) {
            // PAYID_DEFAULT_DRIVER: update in-place if already exists, append if not
            if ($key === 'PAYID_DEFAULT_DRIVER') {
                if (preg_match('/^PAYID_DEFAULT_DRIVER=.*/m', $envContent)) {
                    $envContent = preg_replace(
                        '/^PAYID_DEFAULT_DRIVER=.*/m',
                        "PAYID_DEFAULT_DRIVER={$configKey}",
                        $envContent,
                    );
                    File::put($envPath, $envContent);
                    $updated[] = $key;

                    continue;
                }
                // Not present — will be appended below
                $stub .= "{$key}={$configKey}\n";
                $hasNew = true;
                $appended[] = $key;

                continue;
            }

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

        foreach ($updated as $key) {
            $this->line("  <fg=blue>~ {$key} updated</>");
        }

        foreach ($appended as $key) {
            $this->line("  <fg=gray>+ {$key}</>");
        }

        if (empty($updated) && empty($appended)) {
            $this->line('  <fg=gray>All .env keys already present — nothing added.</>');
        }
    }

    // -------------------------------------------------------------------------
    // Next steps summary
    // -------------------------------------------------------------------------

    protected function printNextSteps(string $driver, string $stack, string $configKey): void
    {
        $withTransactions = $stack === self::STACK_TRANSACTIONS;
        $credentialKeys = $this->credentialEnvKeys($driver);

        $this->newLine();
        $this->components->info('Setup complete! Follow these steps to finish:');
        $this->newLine();

        // Step 1 — fill credentials
        $this->line('  <options=bold>1. Fill in your credentials in <comment>.env</comment>:</>');
        $this->newLine();

        foreach ($credentialKeys as $key) {
            $this->line("     <fg=yellow>{$key}=<your-value-here></>");
        }

        $this->newLine();

        // Step 2 — note about PAYID_DEFAULT_DRIVER (already set by appendEnvVariables)
        $this->line('  <options=bold>2. Your default driver is already set in <comment>.env</comment>:</>');
        $this->newLine();
        $this->line("     <fg=gray>PAYID_DEFAULT_DRIVER={$configKey}</>");

        $step = 3;

        // Step 3 (optional) — payid-transactions migrations
        if ($withTransactions) {
            $this->newLine();
            $this->line("  <options=bold>{$step}. Publish and run payid-transactions migrations:</>");
            $this->newLine();
            $this->line('     <fg=green>php artisan vendor:publish --tag=payid-transactions-migrations</>');
            $this->line('     <fg=green>php artisan vendor:publish --tag=payid-transactions-config</>');
            $this->line('     <fg=green>php artisan migrate</>');
            $step++;
        }

        // Step N — clear config cache
        $this->newLine();
        $this->line("  <options=bold>{$step}. Clear config cache:</>");
        $this->newLine();
        $this->line('     <fg=green>php artisan config:clear</>');

        $this->newLine();
        $this->line('  <fg=gray>Documentation: https://github.com/aliziodev/payid</>');
        $this->newLine();
    }

    /**
     * Return only credential env keys (exclude PAYID_DEFAULT_DRIVER and *_ENV).
     *
     * @return string[]
     */
    protected function credentialEnvKeys(string $driver): array
    {
        return array_keys(array_filter(
            $this->drivers[$driver]['env'] ?? [],
            fn (string $key) => ! in_array($key, ['PAYID_DEFAULT_DRIVER'])
                && ! str_ends_with($key, '_ENV'),
            ARRAY_FILTER_USE_KEY,
        ));
    }
}
