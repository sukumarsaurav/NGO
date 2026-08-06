<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignFaq;
use App\Models\CampaignProduct;
use App\Models\CampaignStat;
use Database\Seeders\CampaignFaqSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('renders no Products section and no anchor link for a campaign with no products', function () {
    $campaign = Campaign::factory()->active()->create();

    $response = $this->get(route('campaigns.show', $campaign->slug));

    $response->assertOk()
        ->assertDontSee('id="products"', false)
        ->assertDontSeeText('Products');
});

it('renders the Products section and steppers when a campaign has catalogue items', function () {
    $campaign = Campaign::factory()->active()->create();
    CampaignProduct::factory()->for($campaign)->create(['name' => 'Medicine kit', 'unit_price' => 90000, 'units_needed' => 1500, 'units_funded' => 11]);

    $response = $this->get(route('campaigns.show', $campaign->slug));

    // "11 / 1500" + a separate "1%" — see
    // docs/13-CAMPAIGN-DETAIL-DESIGN-AUDIT-VS-REFERENCE.md PR F: the fraction dropped its
    // trailing "Donated" when the card became icon-led rather than photo-led.
    $response->assertOk()->assertSeeText('Medicine kit')->assertSeeText('11 / 1500');
});

it('shows global FAQs on every campaign and campaign-specific FAQs only on their own campaign', function () {
    $this->seed(CampaignFaqSeeder::class);

    $campaignA = Campaign::factory()->active()->create();
    $campaignB = Campaign::factory()->active()->create();
    CampaignFaq::query()->create(['campaign_id' => $campaignA->id, 'question' => 'Is this campaign audited separately?', 'answer' => 'Yes.', 'is_published' => true]);

    $responseA = $this->get(route('campaigns.show', $campaignA->slug));
    $responseB = $this->get(route('campaigns.show', $campaignB->slug));

    $responseA->assertOk()
        ->assertSeeText('Do I get a tax benefit for donating?')
        ->assertSeeText('Is this campaign audited separately?');

    $responseB->assertOk()
        ->assertSeeText('Do I get a tax benefit for donating?')
        ->assertDontSeeText('Is this campaign audited separately?');
});

it('does not show an unpublished FAQ', function () {
    $campaign = Campaign::factory()->active()->create();
    CampaignFaq::query()->create(['campaign_id' => $campaign->id, 'question' => 'Hidden question?', 'answer' => 'Hidden answer.', 'is_published' => false]);

    $this->get(route('campaigns.show', $campaign->slug))->assertOk()->assertDontSeeText('Hidden question?');
});

it('renders campaign impact stats', function () {
    $campaign = Campaign::factory()->active()->create();
    CampaignStat::query()->create(['campaign_id' => $campaign->id, 'label' => 'Dogs Rescued', 'value' => '5,000', 'suffix' => '+', 'sort_order' => 0]);

    $this->get(route('campaigns.show', $campaign->slug))->assertOk()->assertSeeText('5,000+')->assertSeeText('Dogs Rescued');
});

it('produces valid FAQPage JSON-LD only when FAQs exist, and no Product or Offer markup', function () {
    $this->seed(CampaignFaqSeeder::class);
    $campaign = Campaign::factory()->active()->create();
    CampaignProduct::factory()->for($campaign)->create();

    $html = $this->get(route('campaigns.show', $campaign->slug))->getContent();

    expect($html)->toContain('"@type":"FAQPage"')
        ->and($html)->not->toContain('"@type":"Product"')
        ->and($html)->not->toContain('"@type":"Offer"');
});
