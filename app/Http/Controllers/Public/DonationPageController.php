<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\ImpactStat;
use Illuminate\View\View;

/**
 * Plain page shells — all the actual behaviour lives in the DonationForm and
 * DonationPending Livewire components. See docs/06-UI-UX-FOUNDATION.md §5-6.
 */
class DonationPageController extends Controller
{
    public function show(): View
    {
        // The undirected-donation entry point reached from the header's "Donate" button on
        // every page — it used to be a bare heading and the form, none of the trust content
        // a campaign page carries. Reuses the homepage's own impact numbers rather than
        // maintaining a second copy. See docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §4.
        return view('public.donate.show', [
            'impactStats' => ImpactStat::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function success(string $uuid): View
    {
        $donation = Donation::query()->where('uuid', $uuid)->firstOrFail();

        return view('public.donate.success', ['donation' => $donation]);
    }

    public function pending(string $uuid): View
    {
        $donation = Donation::query()->where('uuid', $uuid)->firstOrFail();

        if ($donation->status === DonationStatus::Succeeded) {
            return view('public.donate.success', ['donation' => $donation]);
        }

        return view('public.donate.pending', ['donation' => $donation]);
    }

    public function failed(string $uuid): View
    {
        $donation = Donation::query()->where('uuid', $uuid)->firstOrFail();

        return view('public.donate.failed', ['donation' => $donation]);
    }
}
