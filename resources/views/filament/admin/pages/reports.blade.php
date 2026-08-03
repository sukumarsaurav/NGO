@php
    $activeTab = collect($reportTabs)->firstWhere('key', $activeReport);
    $results = $this->results();
    $fyOptions = collect(range(0, 5))->map(fn ($i) => \App\Support\FinancialYear::for(now()->subYears($i))->toString())->unique()->values();
@endphp

<x-filament-panels::page>
    <div style="border-radius:0.75rem;border:1px solid #86816f;background:#ffffff;padding:1rem;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;">
            @foreach ($reportTabs as $tab)
                <button
                    type="button"
                    wire:click="selectReport('{{ $tab['key'] }}')"
                    style="border-radius:0.375rem;padding:0.375rem 0.75rem;font-size:0.875rem;font-weight:500;{{ $tab['key'] === $activeReport ? 'background:#1f7a4d;color:#ffffff;' : 'background:#f0eee7;color:#55524a;' }}"
                >
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($activeTab['fyScoped'])
        <div style="margin-top:1rem;border-radius:0.75rem;border:1px solid #86816f;background:#ffffff;padding:1rem;">
            <label style="font-size:0.75rem;color:#55524a;">Financial year</label>
            <select wire:model.live="financialYear" style="margin-top:0.25rem;display:block;border-radius:0.375rem;border:1px solid #86816f;font-size:0.875rem;padding:0.375rem 0.5rem;">
                @foreach ($fyOptions as $fy)
                    <option value="{{ $fy }}">{{ $fy }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div style="margin-top:1rem;border-radius:0.75rem;border:1px solid #86816f;background:#ffffff;padding:1.5rem;">
        @if ($results->isEmpty())
            <p style="padding-top:2rem;padding-bottom:2rem;text-align:center;font-size:0.875rem;color:#55524a;">
                No data for {{ $activeTab['label'] }}@if ($activeTab['fyScoped']) in FY {{ $financialYear }} @endif.
            </p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
                    @switch ($activeReport)
                        @case ('donation_ledger')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Date</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Receipt No</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Donor</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Amount</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Mode</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Campaign</th>
                                    <th style="padding-bottom:0.5rem;">Status</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $donation)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $donation->donated_at?->format('d M Y') }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $donation->receipts->first()?->receipt_number ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $donation->donor->name }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($donation->amount / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $donation->payment_mode?->label() ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $donation->campaign->title ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $donation->status->label() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break

                        @case ('donor_summary')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Donor</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Total Donated</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Count</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">First</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Last</th>
                                    <th style="padding-bottom:0.5rem;">PAN</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $row)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $row->name }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($row->total_donated / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $row->donation_count }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ \Illuminate\Support\Carbon::parse($row->first_donation_at)->format('d M Y') }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ \Illuminate\Support\Carbon::parse($row->last_donation_at)->format('d M Y') }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $row->pan ? 'Yes' : 'No' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break

                        @case ('subscription_health')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Donor</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Amount</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Status</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Cycles</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Collected</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Next Charge</th>
                                    <th style="padding-bottom:0.5rem;">Failures</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $subscription)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $subscription->donor->name }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($subscription->amount / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $subscription->status->label() }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $subscription->completed_cycles }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($subscription->total_collected / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $subscription->next_charge_at?->format('d M Y') ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $subscription->failed_charge_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break

                        @case ('campaign_performance')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Campaign</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Goal</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Raised</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">% Funded</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Donors</th>
                                    <th style="padding-bottom:0.5rem;">Status</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $campaign)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $campaign->title }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($campaign->goal_amount / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($campaign->displayedRaisedAmount() / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $campaign->goal_amount > 0 ? round(($campaign->displayedRaisedAmount() / $campaign->goal_amount) * 100, 1) : 0 }}%</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $campaign->donor_count }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $campaign->status->label() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break

                        @case ('member_roster')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Code</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Name</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Department</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Designation</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Status</th>
                                    <th style="padding-bottom:0.5rem;">ID Valid Until</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $member)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $member->member_code }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $member->user->name }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $member->department->name ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $member->designation->title ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $member->status->label() }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $member->valid_until?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break

                        @case ('document_issue_log')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Doc No</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Type</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Member</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Issued By</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Date</th>
                                    <th style="padding-bottom:0.5rem;">Status</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $document)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $document->document_number }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $document->type->label() }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $document->member->user->name }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $document->issuedBy->name ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $document->issued_on?->format('d M Y') ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $document->status->label() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break

                        @case ('payment_reconciliation')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Date</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Gross</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Fee</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Tax</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Expected Bank Credit</th>
                                    <th style="padding-bottom:0.5rem;">Transactions</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $row)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $row->captured_date }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($row->gross / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($row->total_fee / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($row->total_tax / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($row->expected_bank_credit / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $row->transaction_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break

                        @case ('failed_payments')
                            <thead>
                                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Date</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Donor</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Amount</th>
                                    <th style="padding-bottom:0.5rem;padding-right:1rem;">Error Code</th>
                                    <th style="padding-bottom:0.5rem;">Description</th>
                                </tr>
                            </thead>
                            <tbody >
                                @foreach ($results as $transaction)
                                    <tr>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $transaction->created_at?->format('d M Y, H:i') }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $transaction->donation->donor->name }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($transaction->amount / 100, 2) }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $transaction->error_code ?? '—' }}</td>
                                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $transaction->error_description ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @break
                    @endswitch
                </table>
            </div>

            <div style="margin-top:1rem;display:flex;align-items:center;justify-content:space-between;font-size:0.875rem;color:#55524a;">
                <span>Page {{ $results->currentPage() }} of {{ $results->lastPage() }} ({{ $results->total() }} rows)</span>
                <div style="display:flex;gap:0.5rem;">
                    <button type="button" wire:click="gotoPage({{ $results->currentPage() - 1 }})" @if ($results->onFirstPage()) disabled @endif style="border-radius:0.375rem;border:1px solid #86816f;padding:0.25rem 0.5rem;">Prev</button>
                    <button type="button" wire:click="gotoPage({{ $results->currentPage() + 1 }})" @if (! $results->hasMorePages()) disabled @endif style="border-radius:0.375rem;border:1px solid #86816f;padding:0.25rem 0.5rem;">Next</button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
