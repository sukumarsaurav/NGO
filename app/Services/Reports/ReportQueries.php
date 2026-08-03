<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\IssuedDocument;
use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * One query builder per report, shared verbatim by the on-screen Reports
 * page and the Excel/CSV export classes — see
 * docs/modules/M12-reports-analytics.md: "Never write the aggregation
 * logic twice; extract it and share it." Each method returns an
 * unexecuted Builder so callers can `->paginate()` on screen or hand it
 * straight to `FromQuery` for a streamed export.
 */
final class ReportQueries
{
    /**
     * Donation ledger — date, receipt no, donor, PAN, amount, mode,
     * campaign, status, fee, net. Fee/net come from the donation's
     * captured payment transaction, if any (offline donations have none).
     */
    public function donationLedger(string $financialYear): Builder
    {
        return Donation::query()
            ->with(['donor', 'campaign', 'receipts'])
            ->leftJoin('payment_transactions', function ($join) {
                $join->on('payment_transactions.donation_id', '=', 'donations.id')
                    ->where('payment_transactions.status', 'captured');
            })
            ->where('donations.financial_year', $financialYear)
            ->select('donations.*', 'payment_transactions.fee as gateway_fee', 'payment_transactions.net_amount')
            ->orderByDesc('donations.donated_at');
    }

    /**
     * Donor summary for the FY — total donated, count, first/last
     * donation (within the FY's own donations), PAN status.
     */
    public function donorSummary(string $financialYear): QueryBuilder
    {
        return DB::table('donations')
            ->join('donors', 'donors.id', '=', 'donations.donor_id')
            ->where('donations.financial_year', $financialYear)
            ->where('donations.status', 'succeeded')
            ->groupBy('donors.id', 'donors.name', 'donors.email', 'donors.pan')
            ->selectRaw('donors.id as donor_id, donors.name, donors.email, donors.pan, '.
                'sum(donations.amount) as total_donated, count(*) as donation_count, '.
                'min(donations.donated_at) as first_donation_at, max(donations.donated_at) as last_donation_at')
            ->orderByDesc('total_donated');
    }

    /**
     * Subscription health — not FY-scoped, these are ongoing mandates.
     */
    public function subscriptionHealth(): Builder
    {
        return Subscription::query()
            ->with('donor')
            ->orderByDesc('total_collected');
    }

    /**
     * Campaign performance — reads the denormalised `raised_amount` /
     * `donor_count` columns, never re-sums `donations` directly (see
     * Campaign model: those columns are kept in sync by a listener plus a
     * nightly reconciler, and are the authoritative figures everywhere
     * else in the app too).
     */
    public function campaignPerformance(): Builder
    {
        return Campaign::query()->withTrashed()->orderByDesc('raised_amount');
    }

    /**
     * Member roster, optionally scoped to a set of department IDs — the
     * Manager panel's version of this report passes its own
     * `managedDepartmentIds()`. See docs/modules/M12-reports-analytics.md:
     * "Manager-scoped versions of member and document reports."
     *
     * @param  list<int>|null  $departmentIds
     */
    public function memberRoster(?array $departmentIds = null): Builder
    {
        return Member::query()
            ->with(['user', 'department', 'designation'])
            ->when($departmentIds !== null, fn (Builder $q) => $q->whereIn('department_id', $departmentIds))
            ->orderBy('member_code');
    }

    /**
     * Document issue log, optionally scoped by the issuing member's
     * department.
     *
     * @param  list<int>|null  $departmentIds
     */
    public function documentIssueLog(?array $departmentIds = null): Builder
    {
        return IssuedDocument::query()
            ->with(['member.user', 'issuedBy'])
            ->when(
                $departmentIds !== null,
                fn (Builder $q) => $q->whereHas('member', fn (Builder $m) => $m->whereIn('department_id', $departmentIds))
            )
            ->orderByDesc('issued_on');
    }

    /**
     * Payment reconciliation — gross, gateway fee, tax, net, grouped by
     * captured date. "Expected bank credit" is the net amount: what the
     * gateway actually settles, after its own fee and tax.
     */
    public function paymentReconciliation(string $financialYear): QueryBuilder
    {
        return DB::table('payment_transactions')
            ->join('donations', 'donations.id', '=', 'payment_transactions.donation_id')
            ->where('donations.financial_year', $financialYear)
            ->where('payment_transactions.status', 'captured')
            ->selectRaw('date(payment_transactions.captured_at) as captured_date, '.
                'sum(payment_transactions.amount) as gross, sum(payment_transactions.fee) as total_fee, '.
                'sum(payment_transactions.tax) as total_tax, sum(payment_transactions.net_amount) as expected_bank_credit, '.
                'count(*) as transaction_count')
            ->groupBy('captured_date')
            ->orderByDesc('captured_date');
    }

    /**
     * Failed payments — date, donor, amount, error code, description.
     */
    public function failedPayments(string $financialYear): Builder
    {
        return PaymentTransaction::query()
            ->with('donation.donor')
            ->whereHas('donation', fn (Builder $q) => $q->where('financial_year', $financialYear))
            ->where('status', 'failed')
            ->orderByDesc('created_at');
    }
}
