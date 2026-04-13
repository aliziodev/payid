<?php

namespace Aliziodev\PayId\Events;

use Aliziodev\PayId\DTO\ChargeResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCharged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ChargeResponse $response,
    ) {}
}
