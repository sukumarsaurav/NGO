<?php

declare(strict_types=1);

namespace App\Livewire\Donations;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Livewire\Component;

/**
 * "Payment succeeded but the callback POST was lost" — the second-most
 * common real-world donation scenario after "donor closes the tab". Polls
 * for 20 seconds (wire:poll.3s here, capped client-side by the view), then
 * reassures rather than erroring. See docs/06-UI-UX-FOUNDATION.md §6.
 */
class DonationPending extends Component
{
    public string $donationUuid;

    public int $pollCount = 0;

    public bool $gaveUp = false;

    public function mount(string $donationUuid): void
    {
        $this->donationUuid = $donationUuid;
    }

    public function poll(): void
    {
        $this->pollCount++;

        $donation = Donation::query()->where('uuid', $this->donationUuid)->first();

        if (! $donation) {
            return;
        }

        if ($donation->status === DonationStatus::Succeeded) {
            $this->redirectRoute('donate.success', ['donation' => $donation->uuid]);

            return;
        }

        if ($donation->status === DonationStatus::Failed) {
            $this->redirectRoute('donate.failed', ['donation' => $donation->uuid]);

            return;
        }

        // 20s cap at 3s intervals ≈ 7 polls — see the acceptance criterion
        // in docs/03-ROADMAP.md's Sprint 6 section.
        if ($this->pollCount >= 7) {
            $this->gaveUp = true;
        }
    }

    public function render()
    {
        return view('livewire.donations.donation-pending');
    }
}
