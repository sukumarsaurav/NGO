<?php

declare(strict_types=1);

use App\Livewire\Donations\DonationForm;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('syncs selected items from the catalogue-updated event and computes the total', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    Livewire::test(DonationForm::class, ['campaignId' => $campaign->id])
        ->set('amount', '500')
        ->call('syncSelectedItems', [['product_id' => $kit->id, 'quantity' => 2]])
        ->assertSet('selectedItems.0.quantity', 2)
        ->assertSet('selectedItems.0.expected_unit_price', 90000);
});

it('requires an explicit confirmation before opening the gateway when a catalogue price changed since selection', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    $component = Livewire::test(DonationForm::class, ['campaignId' => $campaign->id])
        ->set('name', 'Price Change Donor')
        ->set('email', 'pricechange@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', true)
        ->set('amount', '0')
        ->call('syncSelectedItems', [['product_id' => $kit->id, 'quantity' => 1]]);

    // Admin edits the price mid-session, after the donor's last render.
    $kit->update(['unit_price' => 120000]);

    $component->call('donate')
        ->assertSet('priceChangeConfirmationNeeded', true)
        ->assertSet('priceChangeNewTotalPaise', 120000)
        ->assertSet('priceChangeOldTotalPaise', 90000)
        ->assertNotDispatched('donation-initiated');

    // Confirming proceeds and opens the gateway with the corrected total.
    $component->call('donate')->assertDispatched('donation-initiated');
});

it('does not require confirmation when the catalogue price has not changed', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    Livewire::test(DonationForm::class, ['campaignId' => $campaign->id])
        ->set('name', 'Stable Price Donor')
        ->set('email', 'stableprice@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', true)
        ->set('amount', '0')
        ->call('syncSelectedItems', [['product_id' => $kit->id, 'quantity' => 1]])
        ->call('donate')
        ->assertSet('priceChangeConfirmationNeeded', false)
        ->assertDispatched('donation-initiated');
});
