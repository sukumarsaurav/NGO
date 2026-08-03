<?php

declare(strict_types=1);

namespace App\Actions\Campaigns;

use App\Enums\FundraiserRequestStatus;
use App\Mail\FundraiserRequestApprovedMail;
use App\Models\Campaign;
use App\Models\FundraiserRequest;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Approving a fundraiser request creates a draft campaign pre-filled from
 * it and notifies the requester. See
 * docs/modules/M08-campaigns-crowdfunding.md's "Fundraiser requests".
 */
final class ApproveFundraiserRequest
{
    public function __construct(
        private readonly CreateCampaign $createCampaign,
    ) {}

    public function handle(FundraiserRequest $request, ?int $reviewedByUserId = null): FundraiserRequest
    {
        if ($request->status !== FundraiserRequestStatus::New && $request->status !== FundraiserRequestStatus::UnderReview) {
            throw new InvalidArgumentException(
                "Only a new or under-review request can be approved; this one is '{$request->status->value}'."
            );
        }

        if (! $request->cause_category_id) {
            throw new InvalidArgumentException('A cause category must be set before approving this request.');
        }

        $campaign = $this->createCampaign->handle([
            'title' => $request->title,
            'category_id' => $request->cause_category_id,
            'story' => $request->description,
            'goal_amount' => $request->goal_amount,
            'beneficiary_name' => $request->organisation_name ?: $request->name,
            'status' => 'draft',
        ]);

        $request->update([
            'status' => FundraiserRequestStatus::Approved->value,
            'reviewed_by_user_id' => $reviewedByUserId,
            'reviewed_at' => now(),
            'campaign_id' => $campaign->id,
        ]);

        Mail::to($request->email)->queue(new FundraiserRequestApprovedMail($request->fresh()));

        return $request->fresh();
    }
}
