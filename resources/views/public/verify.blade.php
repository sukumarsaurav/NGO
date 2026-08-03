<x-layout.guest title="Document verification">
    @if ($state === 'valid')
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-success-bg text-success-text">
                &#10003;
            </div>
            <h1 class="mb-1 text-xl font-bold text-content">Valid document</h1>
            <p class="mb-6 text-sm text-content-muted">This document was issued by {{ config('app.name') }}.</p>

            <dl class="space-y-3 text-left">
                <div>
                    <dt class="text-sm text-content-muted">Member name</dt>
                    <dd class="font-semibold text-content">{{ $document->snapshot_data['name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Member code</dt>
                    <dd class="font-semibold text-content">{{ $document->snapshot_data['member_code'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Designation</dt>
                    <dd class="font-semibold text-content">{{ $document->snapshot_data['designation'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Document</dt>
                    <dd class="font-semibold text-content">{{ $document->title }} ({{ $document->document_number }})</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Issued on</dt>
                    <dd class="font-semibold text-content">{{ $document->issued_on->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>
    @elseif ($state === 'revoked')
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-danger-bg text-danger-text">
                &#10007;
            </div>
            <h1 class="mb-1 text-xl font-bold text-content">REVOKED</h1>
            <p class="text-sm text-content-muted">
                {{ $document->revoked_reason ?: 'This document has been revoked and is no longer valid.' }}
            </p>
        </div>
    @elseif ($state === 'superseded')
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-warning-bg text-warning-text">
                !
            </div>
            <h1 class="mb-1 text-xl font-bold text-content">Superseded</h1>
            <p class="text-sm text-content-muted">A newer version of this document has since been issued.</p>
        </div>
    @else
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-surface-muted text-content-muted">
                ?
            </div>
            <h1 class="mb-1 text-xl font-bold text-content">Not found</h1>
            <p class="text-sm text-content-muted">This code doesn't match any document we've issued.</p>
        </div>
    @endif
</x-layout.guest>
