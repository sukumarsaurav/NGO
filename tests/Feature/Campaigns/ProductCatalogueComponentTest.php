<?php

declare(strict_types=1);

use App\Livewire\Campaigns\ProductCatalogue;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use Livewire\Livewire;

it('increments and decrements a product quantity and dispatches catalogue-updated', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create(['unit_price' => 90000]);

    Livewire::test(ProductCatalogue::class, ['campaignId' => $campaign->id])
        ->call('increment', $kit->id)
        ->assertSet("quantities.{$kit->id}", 1)
        ->assertDispatched('catalogue-updated', items: [['product_id' => $kit->id, 'quantity' => 1]])
        ->call('increment', $kit->id)
        ->assertSet("quantities.{$kit->id}", 2)
        ->call('decrement', $kit->id)
        ->assertSet("quantities.{$kit->id}", 1);
});

it('never decrements a quantity below zero', function () {
    $campaign = Campaign::factory()->active()->create();
    $kit = CampaignProduct::factory()->for($campaign)->create();

    Livewire::test(ProductCatalogue::class, ['campaignId' => $campaign->id])
        ->call('decrement', $kit->id)
        ->assertSet("quantities.{$kit->id}", 0);
});

it('only lists active products for the campaign', function () {
    $campaign = Campaign::factory()->active()->create();
    CampaignProduct::factory()->for($campaign)->create(['name' => 'Active item', 'is_active' => true]);
    CampaignProduct::factory()->for($campaign)->create(['name' => 'Inactive item', 'is_active' => false]);

    Livewire::test(ProductCatalogue::class, ['campaignId' => $campaign->id])
        ->assertSeeText('Active item')
        ->assertDontSeeText('Inactive item');
});
