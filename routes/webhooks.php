<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\RazorpayWebhookController;
use Illuminate\Support\Facades\Route;

/*
 * CSRF-exempt (see bootstrap/app.php) — HMAC signature verification via the
 * verify.razorpay.webhook middleware is this route's real auth boundary.
 */
Route::post('/webhooks/razorpay', RazorpayWebhookController::class)
    ->middleware('verify.razorpay.webhook')
    ->name('webhooks.razorpay');
