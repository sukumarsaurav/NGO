<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FundraiserRequest;
use App\Models\User;

/**
 * `create` isn't gated at all — the public "Start a Fundraise" form is
 * submitted by guests, not authenticated users; `SubmitFundraiserRequest`
 * never checks this policy. It exists for completeness of the admin CRUD
 * surface (`viewAny`/`view`) and the review action.
 */
class FundraiserRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_fundraiser_requests');
    }

    public function view(User $user, FundraiserRequest $fundraiserRequest): bool
    {
        return $user->can('view_fundraiser_requests');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FundraiserRequest $fundraiserRequest): bool
    {
        return $user->can('review_fundraiser_requests');
    }

    public function delete(User $user, FundraiserRequest $fundraiserRequest): bool
    {
        return false;
    }

    public function review(User $user, FundraiserRequest $fundraiserRequest): bool
    {
        return $user->can('review_fundraiser_requests');
    }
}
