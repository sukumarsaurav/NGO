@php
    $results = $this->results();
@endphp

<x-filament-panels::page>
    <div style="border-radius:0.75rem;border:1px solid #d9d4c6;background:#ffffff;padding:1rem;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;">
            <button
                type="button"
                wire:click="selectReport('member_roster')"
                style="border-radius:0.375rem;padding:0.375rem 0.75rem;font-size:0.875rem;font-weight:500;{{ $activeReport === 'member_roster' ? 'background:#0d7c2f;color:#ffffff;' : 'background:#f0eee7;color:#55524a;' }}"
            >
                Member Roster
            </button>
            <button
                type="button"
                wire:click="selectReport('document_issue_log')"
                style="border-radius:0.375rem;padding:0.375rem 0.75rem;font-size:0.875rem;font-weight:500;{{ $activeReport === 'document_issue_log' ? 'background:#0d7c2f;color:#ffffff;' : 'background:#f0eee7;color:#55524a;' }}"
            >
                Document Issue Log
            </button>
        </div>
        <p style="margin-top:0.75rem;font-size:0.75rem;color:#55524a;">
            Scoped to the department(s) you manage — not an org-wide report.
        </p>
    </div>

    <div style="margin-top:1rem;border-radius:0.75rem;border:1px solid #d9d4c6;background:#ffffff;padding:1.5rem;">
        @if ($results->isEmpty())
            <p style="padding-top:2rem;padding-bottom:2rem;text-align:center;font-size:0.875rem;color:#55524a;">No data in your department(s) yet.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
                    @if ($activeReport === 'member_roster')
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
                    @else
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
                    @endif
                </table>
            </div>

            <div style="margin-top:1rem;display:flex;align-items:center;justify-content:space-between;font-size:0.875rem;color:#55524a;">
                <span>Page {{ $results->currentPage() }} of {{ $results->lastPage() }} ({{ $results->total() }} rows)</span>
                <div style="display:flex;gap:0.5rem;">
                    <button type="button" wire:click="gotoPage({{ $results->currentPage() - 1 }})" @if ($results->onFirstPage()) disabled @endif style="border-radius:0.375rem;border:1px solid #d9d4c6;padding:0.25rem 0.5rem;">Prev</button>
                    <button type="button" wire:click="gotoPage({{ $results->currentPage() + 1 }})" @if (! $results->hasMorePages()) disabled @endif style="border-radius:0.375rem;border:1px solid #d9d4c6;padding:0.25rem 0.5rem;">Next</button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
