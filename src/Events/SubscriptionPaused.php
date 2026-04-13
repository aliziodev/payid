<?php

namespace Aliziodev\PayId\Events;

use Aliziodev\PayId\DTO\SubscriptionResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaused
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SubscriptionResponse $response,
    ) {}
}
