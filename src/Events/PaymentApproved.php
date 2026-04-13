<?php

namespace Aliziodev\PayId\Events;

use Aliziodev\PayId\DTO\StatusResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly StatusResponse $response,
    ) {}
}
