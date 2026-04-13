<?php

namespace Aliziodev\PayId\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class WebhookVerificationFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $driver,
        public readonly Request $request,
    ) {}
}
