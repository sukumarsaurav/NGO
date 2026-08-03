<?php

declare(strict_types=1);

use App\Actions\Campaigns\RecalculateCampaignTotals;
use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Donations\RefundDonation;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Donation;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('creates correct donation_items rows and totals for a mixed catalogue + free-amount donation', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['name' => 'Medicine kit', 'unit_price' => 90000]);
    $bag = CampaignProduct::factory()->for($campaign)->create(['name' => 'Rice bag', 'unit_price' => 50000]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Catalogue Donor',
        donorEmail: 'catalogue@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        campaignId: $campaign->id,
        items: [
            ['product_id' => $kit->id, 'quantity' => 2],
            ['product_id' => $bag->id, 'quantity' => 1],
        ],
    );

    $donation = $initiation->donation;

    expect($donation->amount)->toBe(230000)
        ->and($donation->items_amount)->toBe(230000)
        ->and($donation->free_amount)->toBe(0)
        ->and($donation->items)->toHaveCount(2);

    $kitLine = $donation->items->firstWhere('campaign_product_id', $kit->id);
    expect($kitLine->quantity)->toBe(2)
        ->and($kitLine->unit_price)->toBe(90000)
        ->and($kitLine->line_total)->toBe(180000);
});

it('combines catalogue items with a free amount as separate components of the total', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Combo Donor',
        donorEmail: 'combo@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
        campaignId: $campaign->id,
        items: [['product_id' => $kit->id, 'quantity' => 1]],
    );

    expect($initiation->donation->items_amount)->toBe(90000)
        ->and($initiation->donation->free_amount)->toBe(50000)
        ->and($initiation->donation->amount)->toBe(140000);
});

it('ignores a tampered unit_price and computes the line total from the database price', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    // The action only accepts {product_id, quantity} — there is no field for
    // the client to submit a price at all, so a tampered price is not merely
    // rejected, it is architecturally impossible to send.
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Tamper Donor',
        donorEmail: 'tamper@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        campaignId: $campaign->id,
        items: [['product_id' => $kit->id, 'quantity' => 3]],
    );

    expect($initiation->donation->amount)->toBe(270000);
});

it('drops a deactivated product from the order and reports it, keeping the remaining lines', function () {
    $campaign = Campaign::factory()->active()->create();
    $active = CampaignProduct::factory()->for($campaign)->create(['name' => 'Still available', 'unit_price' => 90000]);
    $deactivated = CampaignProduct::factory()->for($campaign)->create(['name' => 'No longer offered', 'unit_price' => 50000, 'is_active' => false]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Drop Donor',
        donorEmail: 'drop@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        campaignId: $campaign->id,
        items: [
            ['product_id' => $active->id, 'quantity' => 1],
            ['product_id' => $deactivated->id, 'quantity' => 1],
        ],
    );

    expect($initiation->donation->amount)->toBe(90000)
        ->and($initiation->donation->items)->toHaveCount(1)
        ->and($initiation->droppedProductNames)->toBe(['No longer offered']);
});

it('increments units_funded on success and the campaign register stays consistent', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000, 'units_funded' => 0]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Funded Donor',
        donorEmail: 'funded@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        campaignId: $campaign->id,
        items: [['product_id' => $kit->id, 'quantity' => 2]],
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_funded1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromPaise($initiation->donation->fresh()->amount),
    ));

    expect($kit->fresh()->units_funded)->toBe(2)
        ->and($campaign->fresh()->raised_amount)->toBe(180000);
});

it('decrements units_funded on a full refund', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Refund Donor',
        donorEmail: 'productrefund@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        campaignId: $campaign->id,
        items: [['product_id' => $kit->id, 'quantity' => 2]],
    );
    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_refund_product1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromPaise($initiation->donation->fresh()->amount),
    ));

    expect($kit->fresh()->units_funded)->toBe(2);

    app(RefundDonation::class)->handle($donation->fresh(), Money::fromPaise($donation->fresh()->amount));

    expect($kit->fresh()->units_funded)->toBe(0)
        ->and($campaign->fresh()->raised_amount)->toBe(0);
});

it('the nightly reconciler corrects a manually-corrupted units_funded', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Reconcile Donor',
        donorEmail: 'reconcile-product@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        campaignId: $campaign->id,
        items: [['product_id' => $kit->id, 'quantity' => 4]],
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_reconcile_product1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromPaise($initiation->donation->fresh()->amount),
    ));

    $kit->update(['units_funded' => 999]);

    $corrected = app(RecalculateCampaignTotals::class)->handle($campaign->fresh());

    expect($corrected)->toBeTrue()
        ->and($kit->fresh()->units_funded)->toBe(4);
});

it('silently skips a line item submitted with zero quantity', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['name' => 'Ignored', 'unit_price' => 90000]);
    $bag = CampaignProduct::factory()->for($campaign)->create(['name' => 'Counted', 'unit_price' => 50000]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Zero Qty Donor',
        donorEmail: 'zeroqty@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        campaignId: $campaign->id,
        items: [
            ['product_id' => $kit->id, 'quantity' => 0],
            ['product_id' => $bag->id, 'quantity' => 1],
        ],
    );

    expect($initiation->donation->amount)->toBe(50000)
        ->and($initiation->donation->items)->toHaveCount(1)
        ->and($initiation->donation->items->first()->campaign_product_id)->toBe($bag->id);
});

it('rejects catalogue items submitted without a campaign', function () {
    app(InitiateDonation::class)->handle(
        donorName: 'No Campaign Donor',
        donorEmail: 'nocampaign@example.com',
        donorPhone: null,
        amount: Money::fromRupees(0),
        items: [['product_id' => 1, 'quantity' => 1]],
    );
})->throws(InvalidArgumentException::class, 'require a campaign');

it('two concurrent donations for the last available unit both succeed — no reservation', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000, 'units_needed' => 1, 'units_funded' => 0]);

    $first = app(InitiateDonation::class)->handle(
        donorName: 'First Donor', donorEmail: 'concurrent1@example.com', donorPhone: null,
        amount: Money::fromRupees(0), campaignId: $campaign->id, items: [['product_id' => $kit->id, 'quantity' => 1]],
    );
    $second = app(InitiateDonation::class)->handle(
        donorName: 'Second Donor', donorEmail: 'concurrent2@example.com', donorPhone: null,
        amount: Money::fromRupees(0), campaignId: $campaign->id, items: [['product_id' => $kit->id, 'quantity' => 1]],
    );

    app(RecordSuccessfulDonation::class)->handle($first->order->orderId, new PaymentResult(
        paymentId: 'pay_c1', orderId: $first->order->orderId, status: 'captured', amount: Money::fromPaise(90000),
    ));
    app(RecordSuccessfulDonation::class)->handle($second->order->orderId, new PaymentResult(
        paymentId: 'pay_c2', orderId: $second->order->orderId, status: 'captured', amount: Money::fromPaise(90000),
    ));

    expect(Donation::query()->where('campaign_id', $campaign->id)->where('status', 'succeeded')->count())->toBe(2)
        ->and($kit->fresh()->units_funded)->toBe(2)
        ->and($kit->fresh()->percentFunded())->toBe(200);
});
