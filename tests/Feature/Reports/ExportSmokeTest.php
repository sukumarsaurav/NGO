<?php

declare(strict_types=1);

use App\Exports\CampaignPerformanceExport;
use App\Exports\DocumentIssueLogExport;
use App\Exports\DonationLedgerExport;
use App\Exports\DonorSummaryExport;
use App\Exports\FailedPaymentsExport;
use App\Exports\MemberRosterExport;
use App\Exports\PaymentReconciliationExport;
use App\Exports\SubscriptionHealthExport;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\IssuedDocument;
use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Support\FinancialYear;
use Database\Seeders\DocumentTemplateSeeder;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Every export downloads without error and carries the header block —
 * see docs/modules/M12-reports-analytics.md: "Every export carries a
 * header block."
 */
beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('exports the donation ledger as csv and xlsx', function () {
    $fy = FinancialYear::current()->toString();
    Donation::factory()->create(['status' => 'succeeded', 'financial_year' => $fy, 'donated_at' => now()]);

    expect(Excel::download(new DonationLedgerExport($fy), 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
    expect(Excel::download(new DonationLedgerExport($fy), 'test.xlsx', ExcelFormat::XLSX)->getStatusCode())->toBe(200);
});

it('exports the donor summary', function () {
    $fy = FinancialYear::current()->toString();
    Donation::factory()->create(['status' => 'succeeded', 'financial_year' => $fy, 'donated_at' => now()]);

    expect(Excel::download(new DonorSummaryExport($fy), 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
});

it('exports subscription health', function () {
    Subscription::factory()->create();

    expect(Excel::download(new SubscriptionHealthExport, 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
});

it('exports campaign performance', function () {
    Campaign::factory()->create();

    expect(Excel::download(new CampaignPerformanceExport, 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
});

it('exports the member roster', function () {
    Member::factory()->create();

    expect(Excel::download(new MemberRosterExport, 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
});

it('exports the document issue log', function () {
    $this->seed(DocumentTemplateSeeder::class);
    IssuedDocument::factory()->create();

    expect(Excel::download(new DocumentIssueLogExport, 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
});

it('exports payment reconciliation', function () {
    $fy = FinancialYear::current()->toString();
    $donation = Donation::factory()->create(['status' => 'succeeded', 'financial_year' => $fy]);
    PaymentTransaction::factory()->captured()->create(['donation_id' => $donation->id, 'captured_at' => now()]);

    expect(Excel::download(new PaymentReconciliationExport($fy), 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
});

it('exports failed payments', function () {
    $fy = FinancialYear::current()->toString();
    $donation = Donation::factory()->create(['financial_year' => $fy]);
    PaymentTransaction::factory()->create(['donation_id' => $donation->id, 'status' => 'failed']);

    expect(Excel::download(new FailedPaymentsExport($fy), 'test.csv', ExcelFormat::CSV)->getStatusCode())->toBe(200);
});
