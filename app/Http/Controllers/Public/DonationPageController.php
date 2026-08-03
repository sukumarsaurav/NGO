<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\View\View;

/**
 * Plain page shells — all the actual behaviour lives in the DonationForm and
 * DonationPending Livewire components. See docs/06-UI-UX-FOUNDATION.md §5-6.
 */
class DonationPageController extends Controller
{
    public function show(): View
    {
        return view('public.donate.show');
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
