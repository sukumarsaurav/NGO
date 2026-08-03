@php
    $data = $receipt->snapshot_data;
@endphp
<div style="padding: 15mm; font-size: 10pt;">
    <table style="width: 100%; border-bottom: 2pt solid #2f7a4f; padding-bottom: 6mm; margin-bottom: 6mm;">
        <tr>
            <td style="font-size: 15pt; font-weight: bold; color: #2f7a4f;">{{ $data['org_name'] }}</td>
            <td style="text-align: right; font-size: 9pt; color: #4b5563;">
                {{ $data['org_address'] }}<br>
                PAN: {{ $data['org_pan'] ?? '—' }}
            </td>
        </tr>
    </table>

    <h1 style="font-size: 14pt; text-align: center; margin: 0 0 2mm;">80G Tax Exemption Certificate</h1>
    <p style="text-align: center; font-size: 9pt; color: #4b5563; margin: 0 0 6mm;">
        Issued under Section 80G of the Income Tax Act, 1961
    </p>

    <table style="width: 100%; margin-bottom: 4mm; font-size: 9pt;">
        <tr>
            <td><strong>Receipt No:</strong> {{ $receipt->receipt_number }}</td>
            <td style="text-align: right;"><strong>Date:</strong> {{ $receipt->issued_on->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>80G Registration No:</strong> {{ $data['org_80g_number'] ?? '—' }}</td>
            <td style="text-align: right;"><strong>12A Registration No:</strong> {{ $data['org_12a_number'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>80G Validity:</strong>
                {{ $data['org_80g_valid_from'] ?? '—' }} to {{ $data['org_80g_valid_to'] ?? '—' }}
            </td>
        </tr>
        <tr><td colspan="2"><strong>Financial Year:</strong> {{ $data['financial_year'] ?? $receipt->financial_year }}</td></tr>
    </table>

    <table style="width: 100%; margin-bottom: 6mm; font-size: 9pt; border-top: 0.5pt solid #d1d5db; padding-top: 3mm;">
        <tr><td style="width: 35%; color: #4b5563;">Received with thanks from</td><td>{{ $data['donor_name'] }}</td></tr>
        <tr><td style="color: #4b5563;">PAN</td><td>{{ $data['donor_pan'] ?? '—' }}</td></tr>
        <tr><td style="color: #4b5563;">Address</td><td>{{ $data['donor_address'] ?? '—' }}</td></tr>
        <tr><td style="color: #4b5563;">Payment mode</td><td>{{ $data['payment_mode'] ?? '—' }}</td></tr>
        <tr><td style="color: #4b5563;">Purpose</td><td>{{ $data['purpose'] ?? 'General Fund' }}</td></tr>
        <tr><td style="color: #4b5563;">Donation reference</td><td>{{ $data['donation_number'] ?? '—' }}</td></tr>
    </table>

    <table style="width: 100%; border: 1pt solid #d1d5db; margin-bottom: 6mm;">
        <tr>
            <td style="padding: 4mm; font-size: 11pt; font-weight: bold;">Amount</td>
            <td style="padding: 4mm; text-align: right; font-size: 13pt; font-weight: bold; color: #2f7a4f;">
                {{ number_format($receipt->amount / 100, 2) }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 0 4mm 4mm; font-size: 9pt; color: #4b5563;">
                Rupees {{ $receipt->amount_in_words }}
            </td>
        </tr>
    </table>

    <p style="font-size: 8pt; color: #4b5563; margin-bottom: 15mm;">
        {{ $data['deduction_statement'] ?? '' }}
    </p>

    <table style="width: 100%;">
        <tr>
            <td style="width: 25mm; vertical-align: bottom;">{!! $qr !!}</td>
            <td style="text-align: right; vertical-align: bottom; font-size: 9pt; color: #4b5563;">
                @if($sealImage)
                    <img src="{{ $sealImage }}" style="height: 20mm; margin-bottom: 2mm;"><br>
                @endif
                @if($signatureImage)
                    <img src="{{ $signatureImage }}" style="height: 12mm; margin-bottom: 2mm;"><br>
                @endif
                {{ $data['signatory_name'] ?? '' }}<br>
                {{ $data['signatory_designation'] ?? '' }}
            </td>
        </tr>
    </table>
</div>
