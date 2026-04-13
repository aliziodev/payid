<?php

use Aliziodev\PayId\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post(
    config('payid.webhook.route_prefix', 'payid').'/webhook/{driver}',
    WebhookController::class,
)
    ->middleware(config('payid.webhook.route_middleware', []))
    ->name('payid.webhook');
