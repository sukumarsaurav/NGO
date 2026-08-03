<?php

declare(strict_types=1);

namespace App\Actions\Campaigns;

use App\Enums\FundraiserRequestStatus;
use App\Mail\FundraiserRequestRejectedMail;
use App\Models\FundraiserRequest;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class RejectFundraiserRequest
{
    public function handle(FundraiserRequest $request, string $reason, ?int $reviewedByUserId = null): FundraiserRequest
    {
        if (in_array($request->status, [FundraiserRequestStatus::Approved, FundraiserRequestStatus::Rejected], true)) {
            throw new InvalidArgumentException(
                "This request has already been '{$request->status->value}' and cannot be rejected again."
            );
        }

        $request->update([
            'status' => FundraiserRequestStatus::Rejected->value,
            'reviewed_by_user_id' => $reviewedByUserId,
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);

        Mail::to($request->email)->queue(new FundraiserRequestRejectedMail($request->fresh()));

        return $request->fresh();
    }
}
