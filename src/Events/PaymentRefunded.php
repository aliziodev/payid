<?php

namespace Aliziodev\PayId\Events;

use Aliziodev\PayId\DTO\RefundResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRefunded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly RefundResponse $response,
    ) {}
}
