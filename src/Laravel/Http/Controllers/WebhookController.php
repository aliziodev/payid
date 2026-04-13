<?php

namespace Aliziodev\PayId\Laravel\Http\Controllers;

use Aliziodev\PayId\Webhooks\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __construct(
        protected readonly WebhookProcessor $processor,
    ) {}

    public function __invoke(Request $request, string $driver): Response
    {
        $result = $this->processor->handle($request, $driver);

        return response($result->message, $result->httpStatus);
    }
}
