<?php

namespace Aliziodev\PayId\Events;

use Aliziodev\PayId\DTO\NormalizedWebhook;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly NormalizedWebhook $webhook,
    ) {}
}
