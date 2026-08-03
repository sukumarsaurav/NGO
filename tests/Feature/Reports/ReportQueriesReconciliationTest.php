<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\PaymentTransaction;
use App\Services\Reports\ReportQueries;
use App\Support\FinancialYear;
use Illuminate\Support\Facades\DB;

/**
 * "Report totals reconcile exactly against raw DB aggregates — verified by
 * test." See docs/modules/M12-reports-analytics.md's build checklist.
 */
it('donation ledger total matches a raw DB sum for the same FY', function () {
    $fy = FinancialYear::current()->toString();

    Donation::factory()->count(5)->create(['status' => 'succeeded', 'financial_year' => $fy, 'donated_at' => now()]);
    Donation::factory()->create(['status' => 'failed', 'financial_year' => $fy, 'donated_at' => now()]);

    $rawTotal = (int) DB::table('donations')->where('financial_year', $fy)->sum('amount');

    $reportTotal = (int) app(ReportQueries::class)->donationLedger($fy)->get()->sum('amount');

    expect($reportTotal)->toBe($rawTotal);
});

it('donor summary total_donated matches a raw DB sum per donor for the FY', function () {
    $fy = FinancialYear::current()->toString();
    $donor = Donor::factory()->create();

    Donation::factory()->count(3)->create([
        'donor_id' => $donor->id,
        'status' => 'succeeded',
        'financial_year' => $fy,
        'donated_at' => now(),
    ]);

    $rawTotal = (int) DB::table('donations')
        ->where('donor_id', $donor->id)
        ->where('financial_year', $fy)
        ->where('status', 'succeeded')
        ->sum('amount');

    $row = app(ReportQueries::class)->donorSummary($fy)->get()->firstWhere('donor_id', $donor->id);

    expect((int) $row->total_donated)->toBe($rawTotal);
});

it('payment reconciliation expected_bank_credit matches a raw DB sum of net_amount for captured transactions in the FY', function () {
    $fy = FinancialYear::current()->toString();

    $donations = Donation::factory()->count(3)->create(['status' => 'succeeded', 'financial_year' => $fy, 'donated_at' => now()]);

    foreach ($donations as $donation) {
        PaymentTransaction::factory()->captured()->create(['donation_id' => $donation->id, 'captured_at' => now()]);
    }

    // A failed transaction in the same FY must not be counted.
    PaymentTransaction::factory()->create([
        'donation_id' => Donation::factory()->create(['financial_year' => $fy])->id,
        'status' => 'failed',
    ]);

    $rawNet = (int) DB::table('payment_transactions')
        ->join('donations', 'donations.id', '=', 'payment_transactions.donation_id')
        ->where('donations.financial_year', $fy)
        ->where('payment_transactions.status', 'captured')
        ->sum('payment_transactions.net_amount');

    $reportNet = (int) app(ReportQueries::class)->paymentReconciliation($fy)->get()->sum('expected_bank_credit');

    expect($reportNet)->toBe($rawNet)->and($rawNet)->toBeGreaterThan(0);
});

it('campaign performance reads the denormalised raised_amount, matching the campaign record itself', function () {
    $campaign = Campaign::factory()->create(['raised_amount' => 500000, 'offline_raised_amount' => 25000]);

    $row = app(ReportQueries::class)->campaignPerformance()->get()->firstWhere('id', $campaign->id);

    expect($row->displayedRaisedAmount())->toBe(525000);
});
