<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CancelledBy;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Payment\PaymentGateway;
use Illuminate\Console\Command;

/**
 * Runs daily at 02:00 — fetches the current gateway-side state of every
 * non-terminal subscription and corrects local drift. Necessary because
 * webhooks get missed (server downtime, deploys, network failures) — see
 * docs/modules/M06-recurring-autopay.md's "Daily reconciliation" section.
 * Without this, a subscription cancelled at the bank six weeks ago still
 * shows as active revenue on the dashboard.
 */
class SyncSubscriptionStatus extends Command
{
    protected $signature = 'subscriptions:sync-status';

    protected $description = 'Reconciles local subscription status against the gateway for every non-terminal subscription';

    public function handle(PaymentGateway $gateway): int
    {
        $corrected = 0;

        Subscription::query()
            ->whereNotIn('status', [
                SubscriptionStatus::Completed->value,
                SubscriptionStatus::Cancelled->value,
                SubscriptionStatus::Expired->value,
            ])
            ->whereNotNull('provider_subscription_id')
            ->each(function (Subscription $subscription) use ($gateway, &$corrected) {
                $mandate = $gateway->fetchMandate((string) $subscription->provider_subscription_id);
                $gatewayStatus = $this->mapGatewayStatus($mandate->status);

                if ($gatewayStatus === null || $gatewayStatus === $subscription->status) {
                    return;
                }

                $subscription->update(array_filter([
                    'status' => $gatewayStatus->value,
                    'cancelled_at' => $gatewayStatus === SubscriptionStatus::Cancelled ? now() : null,
                    'cancelled_by' => $gatewayStatus === SubscriptionStatus::Cancelled ? CancelledBy::Bank->value : null,
                    'ended_at' => $gatewayStatus === SubscriptionStatus::Cancelled ? now() : null,
                ]));

                $corrected++;
            });

        $this->info("Corrected {$corrected} subscription(s) with drifted status.");

        return self::SUCCESS;
    }

    private function mapGatewayStatus(string $razorpayStatus): ?SubscriptionStatus
    {
        return match ($razorpayStatus) {
            'authenticated', 'active' => SubscriptionStatus::Active,
            'pending' => SubscriptionStatus::PendingAuthentication,
            'halted' => SubscriptionStatus::Halted,
            'paused' => SubscriptionStatus::Paused,
            'completed' => SubscriptionStatus::Completed,
            'cancelled' => SubscriptionStatus::Cancelled,
            'expired' => SubscriptionStatus::Expired,
            default => null,
        };
    }
}
