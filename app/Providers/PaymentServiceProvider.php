<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\RazorpayGateway;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the PaymentGateway abstraction. Testing always gets FakeGateway
 * (deterministic, no network) regardless of what's in .env — see
 * docs/modules/M05-donations-payments.md's "gateway abstraction" section.
 *
 * Local/staging also falls back to FakeGateway when no real Razorpay key is
 * configured — this is what lets the full donation flow run end-to-end
 * (order, payment, webhook, receipt, email) before real credentials exist,
 * with zero code change once RAZORPAY_KEY/SECRET are set. Production never
 * falls back silently: a missing key there is a misconfiguration, not a
 * demo mode, so it still constructs RazorpayGateway and lets it fail loudly.
 */
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function ($app) {
            $hasRealKey = filled(config('services.razorpay.key'));

            if ($app->environment('testing') || (! $app->environment('production') && ! $hasRealKey)) {
                return new FakeGateway;
            }

            return new RazorpayGateway(
                key: (string) config('services.razorpay.key'),
                secret: (string) config('services.razorpay.secret'),
                webhookSecret: (string) config('services.razorpay.webhook_secret'),
            );
        });
    }
}
