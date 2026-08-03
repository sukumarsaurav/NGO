<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Campaigns\SubmitFundraiserRequest;
use App\Http\Controllers\Controller;
use App\Models\CampaignCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public "Start a Fundraise" form. See
 * docs/modules/M08-campaigns-crowdfunding.md's "Fundraiser requests".
 * Rate limited (3/hour/IP) at the route level — public forms attract spam.
 */
class FundraiserRequestController extends Controller
{
    public function show(): View
    {
        return view('public.fundraiser.show', [
            'categories' => CampaignCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, SubmitFundraiserRequest $submit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:20'],
            'organisation_name' => ['nullable', 'string', 'max:190'],
            'cause_category_id' => ['nullable', 'exists:campaign_categories,id'],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'max:5000'],
            'goal_amount' => ['required', 'numeric', 'min:1'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $documentPaths = [];

        foreach ($request->file('documents', []) as $file) {
            $documentPaths[] = $file->store('fundraiser-requests', 'local');
        }

        $submit->handle([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'organisation_name' => $data['organisation_name'] ?? null,
            'cause_category_id' => $data['cause_category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'goal_amount' => (int) round(((float) $data['goal_amount']) * 100),
            'documents' => $documentPaths ?: null,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', "Thanks — we've received your request and will be in touch soon.");
    }
}
