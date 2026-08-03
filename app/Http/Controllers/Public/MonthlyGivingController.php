<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Subscriptions\ActivateSubscription;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The mandate setup redirect flow — five states, all full navigations
 * (never a modal). See docs/06-UI-UX-FOUNDATION.md §6, "Recurring mandates
 * are a different flow entirely".
 */
class MonthlyGivingController extends Controller
{
    /**
     * "Redirecting out" state — the interstitial before the jump to
     * Razorpay's hosted authentication page. Never a silent redirect: an
     * unexplained bounce to a bank domain reads as phishing.
     */
    public function redirecting(string $uuid, PaymentGateway $gateway): View
    {
        $subscription = Subscription::query()->where('uuid', $uuid)->firstOrFail();

        return view('public.donate.monthly.redirecting', [
            'subscription' => $subscription,
            // No real Razorpay short_url without real keys — same
            // FakeGateway fallback as the one-time flow. See
            // PaymentServiceProvider.
            'authUrl' => $gateway instanceof FakeGateway
                ? route('donate.monthly.authorize', $subscription->uuid)
                : ($subscription->raw_response['short_url'] ?? route('donate.monthly.authorize', $subscription->uuid)),
        ]);
    }

    /**
     * Fake-mode-only stand-in for Razorpay's own hosted authentication page —
     * lets the full mandate flow be demonstrated before real credentials
     * exist. Never reached when a real gateway is bound.
     */
    public function authorize(string $uuid): View
    {
        $subscription = Subscription::query()->where('uuid', $uuid)->firstOrFail();

        return view('public.donate.monthly.authorize', ['subscription' => $subscription]);
    }

    public function complete(Request $request, string $uuid, ActivateSubscription $activateSubscription): RedirectResponse
    {
        $subscription = Subscription::query()->where('uuid', $uuid)->firstOrFail();

        if ($request->input('decision') === 'approve') {
            $activateSubscription->handle((string) $subscription->provider_subscription_id);

            return redirect()->route('donate.monthly.return', ['uuid' => $uuid, 'status' => 'authorized']);
        }

        return redirect()->route('donate.monthly.return', ['uuid' => $uuid, 'status' => 'declined']);
    }

    /**
     * Handles the donor's return from Razorpay's hosted page. "Authorised"
     * here is a same-request UX shortcut — the webhook remains the
     * idempotent, authoritative activation path regardless of whether the
     * donor's browser ever makes it back.
     */
    public function return(Request $request, string $uuid): View
    {
        $subscription = Subscription::query()->where('uuid', $uuid)->firstOrFail();
        $status = $request->string('status')->value();

        if ($status === 'declined') {
            return view('public.donate.monthly.declined', ['subscription' => $subscription]);
        }

        if ($subscription->status === SubscriptionStatus::Active) {
            return view('public.donate.monthly.authorized', ['subscription' => $subscription]);
        }

        // Authorised at the gateway but the webhook hasn't landed yet —
        // same principle as /donate/pending: never tell someone who just
        // authorised that it failed. A capped meta-refresh stands in for
        // wire:poll here (this page has no Livewire component of its own).
        $attempt = $request->integer('attempt', 0);

        return view('public.donate.monthly.confirming', [
            'subscription' => $subscription,
            'attempt' => $attempt,
            'gaveUp' => $attempt >= 7,
        ]);
    }
}
