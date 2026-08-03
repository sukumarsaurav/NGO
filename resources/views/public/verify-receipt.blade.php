<x-layout.guest title="Receipt verification">
    @if ($state === 'valid')
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-success-bg text-success-text">
                &#10003;
            </div>
            <h1 class="mb-1 text-xl font-bold text-content">Valid receipt</h1>
            <p class="mb-6 text-sm text-content-muted">Issued by {{ config('app.name') }}.</p>

            <dl class="space-y-3 text-left">
                <div>
                    <dt class="text-sm text-content-muted">Receipt number</dt>
                    <dd class="font-semibold text-content">{{ $receipt->receipt_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Donor</dt>
                    <dd class="font-semibold text-content">{{ $receipt->snapshot_data['donor_name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Amount</dt>
                    <dd class="font-semibold text-content">₹{{ number_format($receipt->amount / 100, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Issued on</dt>
                    <dd class="font-semibold text-content">{{ $receipt->issued_on->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>
    @elseif ($state === 'cancelled')
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-danger-bg text-danger-text">
                &#10007;
            </div>
            <h1 class="mb-1 text-xl font-bold text-content">CANCELLED</h1>
            <p class="text-sm text-content-muted">
                {{ $receipt->cancelled_reason ?: 'This receipt has been cancelled.' }}
            </p>
        </div>
    @else
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-surface-muted text-content-muted">
                ?
            </div>
            <h1 class="mb-1 text-xl font-bold text-content">Not found</h1>
            <p class="text-sm text-content-muted">This code doesn't match any receipt we've issued.</p>
        </div>
    @endif
</x-layout.guest>
