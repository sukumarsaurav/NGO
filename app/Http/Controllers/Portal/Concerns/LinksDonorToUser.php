<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Donor;
use Illuminate\Http\Request;

/**
 * Most donors give as guests and never create an account (see
 * docs/02-DATABASE-SCHEMA.md §5: `donors.user_id` is null for guest donors).
 * If a logged-in user's email matches an existing guest donor row, link them
 * here so their donation/subscription history becomes visible — the organic
 * linkage the donor portal presumes.
 */
trait LinksDonorToUser
{
    private function linkedDonor(Request $request): ?Donor
    {
        $user = $request->user();

        if ($user->donor) {
            return $user->donor;
        }

        $donor = Donor::findByEmail($user->email);

        if ($donor && ! $donor->user_id) {
            $donor->update(['user_id' => $user->id]);
        }

        return $donor;
    }
}
