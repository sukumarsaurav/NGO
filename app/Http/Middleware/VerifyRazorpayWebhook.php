<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Payment\PaymentGateway;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every request to /webhooks/razorpay must carry a valid HMAC signature
 * before the controller — and therefore the database — ever sees it. A bad
 * signature returns 400 and writes nothing; see docs/modules/M05-donations-payments.md's
 * idempotency section and Sprint 5's acceptance criteria.
 */
class VerifyRazorpayWebhook
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Razorpay-Signature', '');

        if ($signature === '' || ! $this->gateway->verifyWebhookSignature($request->getContent(), $signature)) {
            return response('Invalid signature', 400);
        }

        return $next($request);
    }
}
