<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Enums\CancelledBy;
use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionCancelledMail;
use App\Models\Subscription;
use App\Services\Payment\PaymentGateway;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Cancellation is permanent and cancels at the gateway too — never just a
 * local flag. RBI e-mandate rules require an accessible cancellation path,
 * and it "must be genuinely easy" — see
 * docs/modules/M06-recurring-autopay.md's donor self-service section.
 */
final class CancelSubscription
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    public function handle(Subscription $subscription, CancelledBy $cancelledBy, ?string $reason = null): Subscription
    {
        if (! $subscription->status->canTransitionTo(SubscriptionStatus::Cancelled)) {
            throw new InvalidArgumentException(
                "Cannot cancel a subscription that is '{$subscription->status->value}'."
            );
        }

        if ($cancelledBy !== CancelledBy::Bank && $cancelledBy !== CancelledBy::Gateway) {
            // Bank/gateway-initiated cancellations are discovered already
            // cancelled at the source (SyncSubscriptionStatus) — nothing to
            // tell Razorpay about. Donor/admin-initiated ones must cancel
            // there first.
            $this->gateway->cancelMandate((string) $subscription->provider_subscription_id);
        }

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled->value,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy->value,
            'cancellation_reason' => $reason,
            'ended_at' => now(),
        ]);

        $subscription = $subscription->fresh();

        Mail::to($subscription->donor->email)->queue(new SubscriptionCancelledMail($subscription));

        return $subscription;
    }
}
