<div style="padding: 15mm; font-size: 11pt;">
    <h1 style="font-size: 15pt; text-align: center; margin: 0 0 4mm;">{{ $orgName }}</h1>
    <p style="text-align: center; font-size: 12pt; color: #4b5563; margin: 0 0 10mm;">
        Annual Donation Statement — FY {{ $financialYear }}
    </p>

    <p style="font-size: 10pt;"><strong>Donor:</strong> {{ $donor->is_anonymous ? 'Anonymous donor' : $donor->name }}</p>
    <p style="font-size: 10pt; margin-bottom: 8mm;"><strong>Email:</strong> {{ $donor->email }}</p>

    <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
        <thead>
            <tr style="border-bottom: 1pt solid #2f7a4f;">
                <th style="text-align: left; padding: 2mm;">Receipt No.</th>
                <th style="text-align: left; padding: 2mm;">Series</th>
                <th style="text-align: left; padding: 2mm;">Date</th>
                <th style="text-align: right; padding: 2mm;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipts as $receipt)
                <tr style="border-bottom: 0.5pt solid #e5e7eb;">
                    <td style="padding: 2mm;">{{ $receipt->receipt_number }}</td>
                    <td style="padding: 2mm;">{{ $receipt->series->value === '80g' ? '80G' : 'Donation' }}</td>
                    <td style="padding: 2mm;">{{ $receipt->issued_on->format('d M Y') }}</td>
                    <td style="padding: 2mm; text-align: right;">{{ number_format($receipt->amount / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 8mm;">
        <tr>
            <td style="font-size: 12pt; font-weight: bold;">Total</td>
            <td style="text-align: right; font-size: 12pt; font-weight: bold; color: #2f7a4f;">
                {{ $total->formatIndian() }}
            </td>
        </tr>
    </table>
</div>
