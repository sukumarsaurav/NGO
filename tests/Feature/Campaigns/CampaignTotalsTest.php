<?php

declare(strict_types=1);

use App\Actions\Campaigns\RecalculateCampaignTotals;
use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Donations\RefundDonation;
use App\Models\Campaign;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

function succeedCampaignDonation(Campaign $campaign, string $email, int $amountRupees = 1500)
{
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Campaign Donor',
        donorEmail: $email,
        donorPhone: null,
        amount: Money::fromRupees($amountRupees),
        campaignId: $campaign->id,
    );

    return app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees($amountRupees),
    ));
}

it('increments raised_amount and donor_count immediately when a donation succeeds', function () {
    $campaign = Campaign::factory()->active()->create(['raised_amount' => 0, 'donor_count' => 0]);

    succeedCampaignDonation($campaign, 'donor1@example.com', 1500);

    $campaign->refresh();
    expect($campaign->raised_amount)->toBe(150000)
        ->and($campaign->donor_count)->toBe(1);
});

it('does not inflate donor_count when the same donor gives to the campaign again', function () {
    $campaign = Campaign::factory()->active()->create(['raised_amount' => 0, 'donor_count' => 0]);

    succeedCampaignDonation($campaign, 'repeat@example.com', 1000);
    succeedCampaignDonation($campaign, 'repeat@example.com', 500);

    $campaign->refresh();
    expect($campaign->raised_amount)->toBe(150000)
        ->and($campaign->donor_count)->toBe(1);
});

it('leaves campaign totals alone for a general (non-campaign) donation', function () {
    $campaign = Campaign::factory()->active()->create(['raised_amount' => 0, 'donor_count' => 0]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'General Donor',
        donorEmail: 'general@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_general',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));

    expect($campaign->fresh()->raised_amount)->toBe(0);
});

it('rejects a donation to a campaign that is not active', function () {
    $campaign = Campaign::factory()->create(['status' => 'paused']);

    app(InitiateDonation::class)->handle(
        donorName: 'Blocked Donor',
        donorEmail: 'blocked@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
        campaignId: $campaign->id,
    );
})->throws(InvalidArgumentException::class, 'not currently accepting donations');

it('decrements totals when a donation to a campaign is fully refunded', function () {
    $campaign = Campaign::factory()->active()->create(['raised_amount' => 0, 'donor_count' => 0]);
    $donation = succeedCampaignDonation($campaign, 'refundee@example.com', 2000);

    app(RefundDonation::class)->handle($donation->fresh(), Money::fromRupees(2000));

    $campaign->refresh();
    expect($campaign->raised_amount)->toBe(0)
        ->and($campaign->donor_count)->toBe(0);
});

it('does not decrement totals on a partial refund', function () {
    $campaign = Campaign::factory()->active()->create(['raised_amount' => 0, 'donor_count' => 0]);
    $donation = succeedCampaignDonation($campaign, 'partial@example.com', 2000);

    app(RefundDonation::class)->handle($donation->fresh(), Money::fromRupees(500));

    expect($campaign->fresh()->raised_amount)->toBe(200000);
});

it('the nightly reconciler corrects a manually-corrupted total', function () {
    $campaign = Campaign::factory()->active()->create(['raised_amount' => 0, 'donor_count' => 0]);
    succeedCampaignDonation($campaign, 'driftcheck@example.com', 750);

    $campaign->update(['raised_amount' => 999999, 'donor_count' => 42]);

    $corrected = app(RecalculateCampaignTotals::class)->handle($campaign);

    expect($corrected)->toBeTrue()
        ->and($campaign->fresh()->raised_amount)->toBe(75000)
        ->and($campaign->fresh()->donor_count)->toBe(1);
});

it('the recalculate-totals command sweeps every campaign', function () {
    $campaign = Campaign::factory()->active()->create(['raised_amount' => 0, 'donor_count' => 0]);
    succeedCampaignDonation($campaign, 'sweep@example.com', 300);

    // Simulate drift as if the fast-path listener never ran.
    $campaign->update(['raised_amount' => 0, 'donor_count' => 0]);

    Artisan::call('campaigns:recalculate-totals');

    expect($campaign->fresh()->raised_amount)->toBe(30000);
});
