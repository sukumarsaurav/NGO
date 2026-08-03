<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Subscriptions\CancelSubscription;
use App\Actions\Subscriptions\PauseSubscription;
use App\Enums\CancelledBy;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\LinksDonorToUser;
use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Donor self-service: view, pause, resume, cancel own mandates. See
 * docs/modules/M06-recurring-autopay.md's "Donor self-service" section —
 * "Cancellation must be genuinely easy."
 */
class SubscriptionsController extends Controller
{
    use LinksDonorToUser;

    public function index(Request $request): View
    {
        $donor = $this->linkedDonor($request);

        return view('portal.subscriptions.index', [
            'subscriptions' => $donor
                ? $donor->subscriptions()->with('charges')->latest('created_at')->get()
                : collect(),
        ]);
    }

    public function pause(Request $request, Subscription $subscription, PauseSubscription $action): RedirectResponse
    {
        $this->authorizeOwnership($request, $subscription);

        $action->handle($subscription);

        return back()->with('status', 'Your monthly donation has been paused.');
    }

    public function resume(Request $request, Subscription $subscription, PauseSubscription $action): RedirectResponse
    {
        $this->authorizeOwnership($request, $subscription);

        $action->resume($subscription);

        return back()->with('status', 'Your monthly donation has been resumed.');
    }

    public function cancel(Request $request, Subscription $subscription, CancelSubscription $action): RedirectResponse
    {
        $this->authorizeOwnership($request, $subscription);

        $action->handle($subscription, CancelledBy::Donor);

        return back()->with('status', 'Your monthly donation has been cancelled.');
    }

    private function authorizeOwnership(Request $request, Subscription $subscription): void
    {
        $donor = $this->linkedDonor($request);

        abort_unless($donor instanceof Donor && $subscription->donor_id === $donor->id, Response::HTTP_FORBIDDEN);
    }
}
