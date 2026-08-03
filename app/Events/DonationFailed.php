<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Donation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DonationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Donation $donation,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorDescription = null,
    ) {}
}
