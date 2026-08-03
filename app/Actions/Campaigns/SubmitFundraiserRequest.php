<?php

declare(strict_types=1);

namespace App\Actions\Campaigns;

use App\Mail\NewFundraiserRequestMail;
use App\Models\FundraiserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * The public "Start a Fundraise" form submission. See
 * docs/modules/M08-campaigns-crowdfunding.md's "Fundraiser requests".
 */
final class SubmitFundraiserRequest
{
    /**
     * @param  array{name: string, email: string, phone: string, organisation_name?: string|null,
     *     cause_category_id?: int|null, title: string, description: string, goal_amount: int,
     *     documents?: array<int, string>|null, ip_address?: string|null}  $attributes
     */
    public function handle(array $attributes): FundraiserRequest
    {
        $attributes['status'] = 'new';

        $request = FundraiserRequest::query()->forceCreate($attributes);

        foreach (User::role(['super-admin', 'admin'])->get() as $admin) {
            Mail::to($admin->email)->queue(new NewFundraiserRequestMail($request));
        }

        return $request;
    }
}
