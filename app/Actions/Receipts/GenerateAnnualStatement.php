<?php

declare(strict_types=1);

namespace App\Actions\Receipts;

use App\Models\Donor;
use App\Models\Receipt;
use App\Services\Pdf\PdfRenderer;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;

/**
 * One summary PDF per donor per financial year — "useful for donors filing
 * returns." Generated on demand (donor portal) or in bulk after year-end.
 * See docs/modules/M07-receipts-80g.md's "Annual consolidated statement"
 * section. Unlike individual receipts, this is not itself a numbered
 * statutory document, so it's rendered on-demand rather than stored.
 */
final class GenerateAnnualStatement
{
    public function __construct(
        private readonly PdfRenderer $renderer,
        private readonly SettingsRepository $settings,
    ) {}

    public function handle(Donor $donor, string $financialYear): string
    {
        $receipts = Receipt::query()
            ->where('donor_id', $donor->id)
            ->where('financial_year', $financialYear)
            ->where('is_cancelled', false)
            ->orderBy('issued_on')
            ->get();

        $total = $receipts->sum('amount');

        $html = view('receipts.annual-statement', [
            'donor' => $donor,
            'financialYear' => $financialYear,
            'receipts' => $receipts,
            'total' => Money::fromPaise($total),
            'orgName' => $this->settings->get('org.name'),
        ])->render();

        return $this->renderer->renderHtml($html, '', 'A4', 'portrait');
    }
}
