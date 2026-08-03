@php
    $data = $receipt->snapshot_data;
@endphp
<div style="padding: 15mm; font-size: 11pt;">
    <table style="width: 100%; border-bottom: 2pt solid #2f7a4f; padding-bottom: 6mm; margin-bottom: 8mm;">
        <tr>
            <td style="font-size: 16pt; font-weight: bold; color: #2f7a4f;">{{ $org['name'] }}</td>
            <td style="text-align: right; font-size: 10pt; color: #4b5563;">
                {{ $org['address_line1'] }}<br>
                @if($org['address_line2']){{ $org['address_line2'] }}<br>@endif
                {{ trim(($org['city'] ?? '').', '.($org['state'] ?? '').' '.($org['pincode'] ?? ''), ', ') }}
            </td>
        </tr>
    </table>

    <h1 style="font-size: 15pt; text-align: center; margin: 0 0 8mm;">Donation Receipt</h1>

    <table style="width: 100%; margin-bottom: 6mm; font-size: 10pt;">
        <tr>
            <td><strong>Receipt No:</strong> {{ $receipt->receipt_number }}</td>
            <td style="text-align: right;"><strong>Date:</strong> {{ $receipt->issued_on->format('d M Y') }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Financial Year:</strong> {{ $receipt->financial_year }}</td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 8mm; font-size: 10pt;">
        <tr><td style="width: 35%; color: #4b5563;">Received with thanks from</td><td>{{ $data['donor_name'] }}</td></tr>
        @if(!empty($data['donor_address']))
        <tr><td style="color: #4b5563;">Address</td><td>{{ $data['donor_address'] }}</td></tr>
        @endif
        @if(!empty($data['donor_pan']))
        <tr><td style="color: #4b5563;">PAN</td><td>{{ $data['donor_pan'] }}</td></tr>
        @endif
        <tr><td style="color: #4b5563;">Payment mode</td><td>{{ $data['payment_mode'] ?? '—' }}</td></tr>
        <tr><td style="color: #4b5563;">Donation reference</td><td>{{ $data['donation_number'] ?? '—' }}</td></tr>
    </table>

    <table style="width: 100%; border: 1pt solid #d1d5db; margin-bottom: 8mm;">
        <tr>
            <td style="padding: 4mm; font-size: 12pt; font-weight: bold;">Amount</td>
            <td style="padding: 4mm; text-align: right; font-size: 14pt; font-weight: bold; color: #2f7a4f;">
                {{ number_format($receipt->amount / 100, 2) }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 0 4mm 4mm; font-size: 9pt; color: #4b5563;">
                Rupees {{ $receipt->amount_in_words }}
            </td>
        </tr>
    </table>

    <p style="font-size: 9pt; color: #4b5563;">
        This receipt acknowledges the donation described above with gratitude.
        A separate 80G tax-exemption receipt, where applicable, will be issued under its own series.
    </p>

    <table style="width: 100%; margin-top: 15mm;">
        <tr>
            <td style="width: 25mm; vertical-align: top;">{!! $qr !!}</td>
            <td style="text-align: right; vertical-align: bottom; font-size: 9pt; color: #4b5563;">
                Authorised signatory<br>{{ $org['name'] }}
            </td>
        </tr>
    </table>
</div>
