<x-filament-panels::page>
    <div style="border-radius:0.75rem;border:1px solid #86816f;background:#ffffff;padding:1.5rem;">
        <h2 style="margin-bottom:1rem;font-size:1rem;font-weight:600;">FY receipt register</h2>
        <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                    <th style="padding-bottom:0.5rem;">Series</th>
                    <th style="padding-bottom:0.5rem;">Financial year</th>
                    <th style="padding-bottom:0.5rem;">Count</th>
                    <th style="padding-bottom:0.5rem;">Cancelled</th>
                    <th style="padding-bottom:0.5rem;text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->register() as $row)
                    <tr>
                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $row['series'] === '80g' ? '80G' : 'Donation' }}</td>
                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $row['financial_year'] }}</td>
                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $row['count'] }}</td>
                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;border-bottom:1px solid #d9d4c6;">{{ $row['cancelled_count'] }}</td>
                        <td style="padding-top:0.5rem;padding-bottom:0.5rem;text-align:right;border-bottom:1px solid #d9d4c6;">&#8377;{{ number_format($row['total'] / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding-top:1rem;padding-bottom:1rem;text-align:center;color:#55524a;">No receipts issued yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.5rem;border-radius:0.75rem;border:1px solid #86816f;background:#ffffff;padding:1.5rem;">
        <div style="margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-size:1rem;font-weight:600;">Gap detection &amp; Form 10BD — financial year</h2>
            <select wire:model.live="financialYear" style="border-radius:0.375rem;border:1px solid #86816f;font-size:0.875rem;padding:0.375rem 0.5rem;">
                @foreach ($this->register()->pluck('financial_year')->unique() as $fy)
                    <option value="{{ $fy }}">{{ $fy }}</option>
                @endforeach
                @if (! $this->register()->pluck('financial_year')->contains($financialYear))
                    <option value="{{ $financialYear }}" selected>{{ $financialYear }}</option>
                @endif
            </select>
        </div>

        @php $gaps = $this->gaps(); @endphp

        <div style="margin-bottom:1rem;">
            <p style="font-size:0.875rem;font-weight:500;">Donation series gaps: {{ count($gaps['donation']) }}</p>
            <p style="font-size:0.875rem;font-weight:500;">80G series gaps: {{ count($gaps['80g']) }}</p>
            @if (count($gaps['donation']) === 0 && count($gaps['80g']) === 0)
                <p style="margin-top:0.25rem;font-size:0.75rem;color:#0d7c2f;">Gap-free — as it should always be.</p>
            @else
                <p style="margin-top:0.25rem;font-size:0.75rem;color:#c0392b;">
                    Missing sequence numbers found. Donation: {{ implode(', ', $gaps['donation']) }}
                    80G: {{ implode(', ', $gaps['80g']) }}
                </p>
            @endif
        </div>

        <div>
            <p style="margin-bottom:0.5rem;font-size:0.875rem;font-weight:500;">Donors missing PAN or address ({{ count($this->missingPan()) }})</p>
            @if (count($this->missingPan()) > 0)
                <ul style="font-size:0.75rem;color:#55524a;">
                    @foreach ($this->missingPan() as $row)
                        <li>{{ $row['donor_name'] }} ({{ $row['donor_email'] }}) — {{ $row['missing_pan'] ? 'PAN' : '' }}{{ $row['missing_pan'] && $row['missing_address'] ? ' & ' : '' }}{{ $row['missing_address'] ? 'address' : '' }} missing</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-filament-panels::page>
