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
        protected readonly mixed $ledger = null,
    ) {}

    /**
     * Proses webhook yang masuk melalui pipeline:
     * verifikasi → parsing → normalisasi → event dispatch
     */
    public function handle(Request $request, string $driverName): WebhookResult
    {
        $payload = $request->all();
        $merchantOrderId = (string) data_get($payload, 'order_id', data_get($payload, 'merchant_order_id', ''));
        $providerTransactionId = (string) data_get($payload, 'transaction_id', '');

        $event = $this->recordWebhookEvent($driverName, $merchantOrderId, $providerTransactionId, $payload);

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
                $this->markWebhookProcessed($event, false, 'Webhook signature verification failed.');

                return WebhookResult::unauthorized('Webhook signature verification failed.');
            }

            $this->markWebhookSignatureValid($event, true);
        }

        // Step 2: Parse dan normalisasi payload
        if (! $driver instanceof SupportsWebhookParsing) {
            $this->logger->warning('payid.webhook.no_parser', ['driver' => $driverName]);
            $this->markWebhookProcessed($event, false, 'Driver does not support webhook parsing.');

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
            $this->markWebhookProcessed($event, false, 'Webhook payload parsing failed.');

            return WebhookResult::unprocessable('Webhook payload parsing failed.');
        }

        $this->upsertStatusFromWebhook($driverName, $normalized);

        // Step 3: Dispatch event
        $this->logger->info('payid.webhook.processed', [
            'driver' => $driverName,
            'order_id' => $normalized->merchantOrderId,
            'status' => $normalized->status->value,
        ]);

        $this->events->dispatch(new WebhookReceived($normalized));
        $this->markWebhookProcessed($event, true);

        return WebhookResult::ok($normalized);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function recordWebhookEvent(
        string $driverName,
        string $merchantOrderId,
        string $providerTransactionId,
        array $payload,
    ): mixed {
        if (! is_object($this->ledger) || ! method_exists($this->ledger, 'recordWebhookEvent')) {
            return null;
        }

        try {
            return $this->ledger->recordWebhookEvent([
                'provider' => $driverName,
                'event_fingerprint' => $this->buildFingerprint($driverName, $merchantOrderId, $providerTransactionId, $payload),
                'merchant_order_id' => $merchantOrderId !== '' ? $merchantOrderId : null,
                'provider_transaction_id' => $providerTransactionId !== '' ? $providerTransactionId : null,
                'signature_valid' => false,
                'payload' => $payload,
                'received_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('payid.ledger.record_webhook_failed', [
                'driver' => $driverName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function markWebhookProcessed(mixed $event, bool $success, ?string $message = null): void
    {
        if ($event === null || ! is_object($this->ledger) || ! method_exists($this->ledger, 'markWebhookProcessed')) {
            return;
        }

        try {
            $this->ledger->markWebhookProcessed($event, $success, $message);
        } catch (Throwable $e) {
            $this->logger->warning('payid.ledger.mark_webhook_processed_failed', [
                'success' => $success,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function upsertStatusFromWebhook(string $driverName, mixed $normalized): void
    {
        if (! is_object($this->ledger) || ! method_exists($this->ledger, 'upsertStatus')) {
            return;
        }

        try {
            $this->ledger->upsertStatus([
                'provider' => $driverName,
                'merchant_order_id' => $normalized->merchantOrderId,
                'provider_transaction_id' => $normalized->providerTransactionId,
                'status' => $normalized->status->value,
                'amount' => $normalized->amount,
                'currency' => $normalized->currency,
                'raw_response' => $normalized->rawPayload,
                'occurred_at' => $normalized->occurredAt,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('payid.ledger.upsert_status_failed', [
                'driver' => $driverName,
                'merchant_order_id' => $normalized->merchantOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function markWebhookSignatureValid(mixed $event, bool $valid): void
    {
        if (! is_object($event)) {
            return;
        }

        try {
            if (method_exists($event, 'forceFill') && method_exists($event, 'save')) {
                $event->forceFill(['signature_valid' => $valid]);
                $event->save();

                return;
            }

            if (method_exists($event, 'update')) {
                $event->update(['signature_valid' => $valid]);

                return;
            }

            if (property_exists($event, 'signature_valid')) {
                $event->signature_valid = $valid;
            }
        } catch (Throwable $e) {
            $this->logger->warning('payid.ledger.mark_signature_valid_failed', [
                'valid' => $valid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function buildFingerprint(string $provider, string $merchantOrderId, string $providerTransactionId, array $payload): string
    {
        $encodedPayload = json_encode($payload);

        if (! is_string($encodedPayload)) {
            $encodedPayload = serialize($payload);
        }

        return hash('sha256', implode('|', [
            $provider,
            $merchantOrderId,
            $providerTransactionId,
            $encodedPayload,
        ]));
    }
}
