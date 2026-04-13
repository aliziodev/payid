<?php

namespace Aliziodev\PayId\Webhooks;

use Aliziodev\PayId\DTO\NormalizedWebhook;

class WebhookResult
{
    public function __construct(
        public readonly bool $success,
        public readonly int $httpStatus,
        public readonly string $message,
        public readonly ?NormalizedWebhook $webhook = null,
    ) {}

    public static function ok(NormalizedWebhook $webhook): self
    {
        return new self(
            success: true,
            httpStatus: 200,
            message: 'OK',
            webhook: $webhook,
        );
    }

    public static function unauthorized(string $reason = 'Unauthorized'): self
    {
        return new self(
            success: false,
            httpStatus: 401,
            message: $reason,
        );
    }

    public static function unprocessable(string $reason = 'Unprocessable'): self
    {
        return new self(
            success: false,
            httpStatus: 422,
            message: $reason,
        );
    }

    public static function noParser(): self
    {
        return new self(
            success: true,
            httpStatus: 200,
            message: 'OK (no parser)',
        );
    }
}
