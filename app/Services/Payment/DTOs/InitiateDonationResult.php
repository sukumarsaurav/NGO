<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Models\Donation;

final class InitiateDonationResult
{
    /**
     * @param  list<string>  $droppedProductNames  names of catalogue items requested
     *                                             but silently dropped — deactivated between page load and submit. See
     *                                             docs/modules/M08-campaigns-crowdfunding.md's "Product deactivated
     *                                             while it sits in someone's selection" edge case.
     */
    public function __construct(
        public readonly Donation $donation,
        public readonly OrderResult $order,
        public readonly array $droppedProductNames = [],
    ) {}
}
