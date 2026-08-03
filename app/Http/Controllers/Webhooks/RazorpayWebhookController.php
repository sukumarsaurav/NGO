<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Donations\RecordFailedDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Subscriptions\ActivateSubscription;
use App\Actions\Subscriptions\RecordSubscriptionCharge;
use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\Payment\DTOs\WebhookEvent as ParsedWebhookEvent;
use App\Services\Payment\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Reached only after VerifyRazorpayWebhook has already confirmed the HMAC
 * signature — everything from here on trusts the payload. Idempotency layer
 * 1 (webhook_events UNIQUE(provider, event_id)) is enforced here; layer 2
 * (row locking) lives inside the individual record-* actions. See
 * docs/modules/M05-donations-payments.md and M06-recurring-autopay.md.
 *
 * One endpoint for every event type — this mirrors reality: Razorpay sends
 * all webhook types (payment.* and subscription.*) to the single URL
 * configured on the account, not to separate endpoints per event.
 */
class RazorpayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        RecordSuccessfulDonation $recordSuccess,
        RecordFailedDonation $recordFailure,
        ActivateSubscription $activateSubscription,
        RecordSubscriptionCharge $recordCharge,
    ): JsonResponse {
        $payload = (array) $request->json()->all();
        $event = $gateway->parseWebhook($payload);

        $webhookEvent = WebhookEvent::query()->firstOrCreate(
            ['provider' => 'razorpay', 'event_id' => $event->eventId],
            ['event_type' => $event->eventType, 'payload' => $payload, 'status' => 'received']
        );

        if (! $webhookEvent->wasRecentlyCreated) {
            // Seen this exact event before — Razorpay's documented retry/
            // duplicate-delivery behaviour. Do nothing, return success.
            return response()->json(['status' => 'duplicate']);
        }

        try {
            if (str_starts_with($event->eventType, 'subscription.')) {
                $this->handleSubscriptionEvent($event, $activateSubscription, $recordCharge);
            } elseif ($event->orderId && $event->paymentId) {
                $this->handlePaymentEvent($event, $gateway, $recordSuccess, $recordFailure);
            } else {
                $webhookEvent->update(['status' => 'ignored']);

                return response()->json(['status' => 'ignored']);
            }

            $webhookEvent->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (RuntimeException $e) {
            // Unknown order_id/subscription_id — never 500 an unrecognised
            // webhook (Razorpay would just retry it forever). Log for admin
            // follow-up instead.
            $webhookEvent->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::warning('Razorpay webhook for unrecognised entity', ['event_type' => $event->eventType]);

            return response()->json(['status' => 'unrecognised']);
        } catch (Throwable $e) {
            $webhookEvent->update(['status' => 'failed', 'error' => $e->getMessage()]);

            throw $e;
        }

        return response()->json(['status' => 'processed']);
    }

    private function handlePaymentEvent(
        ParsedWebhookEvent $event,
        PaymentGateway $gateway,
        RecordSuccessfulDonation $recordSuccess,
        RecordFailedDonation $recordFailure,
    ): void {
        $result = $gateway->fetchPayment((string) $event->paymentId);

        if ($event->eventType === 'payment.captured') {
            $recordSuccess->handle((string) $event->orderId, $result);
        } elseif ($event->eventType === 'payment.failed') {
            $recordFailure->handle((string) $event->orderId, $result);
        }
    }

    private function handleSubscriptionEvent(
        ParsedWebhookEvent $event,
        ActivateSubscription $activateSubscription,
        RecordSubscriptionCharge $recordCharge,
    ): void {
        if (! $event->subscriptionId) {
            throw new RuntimeException("Subscription webhook '{$event->eventType}' has no subscription id.");
        }

        match ($event->eventType) {
            'subscription.activated' => $activateSubscription->handle($event->subscriptionId),
            'subscription.charged' => $recordCharge->recordSuccess(
                $event->subscriptionId,
                (string) $event->paymentId,
                (int) ($event->payload['payload']['payment']['entity']['amount'] ?? 0),
            ),
            'subscription.pending' => $recordCharge->recordFailure(
                $event->subscriptionId,
                $event->payload['payload']['payment']['entity']['error_description'] ?? 'Charge attempt failed',
            ),
            default => null, // halted/cancelled/completed: reconciled by SyncSubscriptionStatus, not acted on here yet.
        };
    }
}
