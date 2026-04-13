<?php

namespace Aliziodev\PayId\Webhooks;

use Aliziodev\PayId\Contracts\SupportsWebhookParsing;
use Aliziodev\PayId\Contracts\SupportsWebhookVerification;
use Aliziodev\PayId\Events\WebhookParsingFailed;
use Aliziodev\PayId\Events\WebhookReceived;
use Aliziodev\PayId\Events\WebhookVerificationFailed;
use Aliziodev\PayId\Managers\PayIdManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Throwable;

class WebhookProcessor
{
    public function __construct(
        protected readonly PayIdManager $manager,
        protected readonly Dispatcher $events,
        protected readonly LoggerInterface $logger,
    ) {}

    /**
     * Proses webhook yang masuk melalui pipeline:
     * verifikasi → parsing → normalisasi → event dispatch
     */
    public function handle(Request $request, string $driverName): WebhookResult
    {
        $this->logger->info('payid.webhook.incoming', [
            'driver' => $driverName,
            'ip' => $request->ip(),
        ]);

        $driver = $this->manager->driver($driverName)->getDriver();

        // Step 1: Verifikasi signature
        if ($driver instanceof SupportsWebhookVerification) {
            $verified = false;

            try {
                $verified = $driver->verifyWebhook($request);
            } catch (Throwable $e) {
                $this->logger->error('payid.webhook.verification_error', [
                    'driver' => $driverName,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->logger->info('payid.webhook.verification', [
                'driver' => $driverName,
                'result' => $verified ? 'passed' : 'failed',
            ]);

            if (! $verified) {
                $this->events->dispatch(new WebhookVerificationFailed($driverName, $request));

                return WebhookResult::unauthorized('Webhook signature verification failed.');
            }
        }

        // Step 2: Parse dan normalisasi payload
        if (! $driver instanceof SupportsWebhookParsing) {
            $this->logger->warning('payid.webhook.no_parser', ['driver' => $driverName]);

            return WebhookResult::noParser();
        }

        try {
            $normalized = $driver->parseWebhook($request);
        } catch (Throwable $e) {
            $this->logger->error('payid.webhook.parsing_failed', [
                'driver' => $driverName,
                'error' => $e->getMessage(),
            ]);

            $this->events->dispatch(new WebhookParsingFailed($driverName, $request, $e));

            return WebhookResult::unprocessable('Webhook payload parsing failed.');
        }

        // Step 3: Dispatch event
        $this->logger->info('payid.webhook.processed', [
            'driver' => $driverName,
            'order_id' => $normalized->merchantOrderId,
            'status' => $normalized->status->value,
        ]);

        $this->events->dispatch(new WebhookReceived($normalized));

        return WebhookResult::ok($normalized);
    }
}
