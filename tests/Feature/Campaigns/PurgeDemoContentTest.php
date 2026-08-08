<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Artisan;

/**
 * `demo:purge` deletes production rows, so its blast radius is the thing under test —
 * not just that it removes the demo set, but that it refuses to touch anything else.
 */
function makeCampaign(string $slug): Campaign
{
    return Campaign::factory()
        ->for(CampaignCategory::factory(), 'category')
        ->create(['slug' => $slug]);
}

it('removes demo campaigns, their donations and their donors', function () {
    $demo = makeCampaign('winter-blankets-for-street-families');
    $donor = Donor::factory()->create();
    Donation::factory()->succeeded()->create(['donor_id' => $donor->id, 'campaign_id' => $demo->id]);

    Artisan::call('demo:purge', ['--no-interaction' => true]);

    expect(Campaign::withTrashed()->find($demo->id))->toBeNull()
        ->and(Donor::find($donor->id))->toBeNull()
        ->and(Donation::where('campaign_id', $demo->id)->count())->toBe(0);
});

it('leaves real campaigns and their data untouched', function () {
    $demo = makeCampaign('community-kitchen-daily-meals');
    $real = makeCampaign('a-genuine-campaign-we-added');

    $realDonor = Donor::factory()->create();
    Donation::factory()->succeeded()->create(['donor_id' => $realDonor->id, 'campaign_id' => $real->id]);

    Artisan::call('demo:purge', ['--no-interaction' => true]);

    expect(Campaign::withTrashed()->find($demo->id))->toBeNull()
        ->and(Campaign::find($real->id))->not->toBeNull()
        ->and(Donor::find($realDonor->id))->not->toBeNull();
});

it('keeps a donor who gave to both a demo and a real campaign', function () {
    $demo = makeCampaign('flood-relief-kits-assam');
    $real = makeCampaign('a-genuine-campaign-we-added');

    // The case that makes a blunt "delete every donor touching a demo campaign" wrong.
    $crossoverDonor = Donor::factory()->create();
    Donation::factory()->succeeded()->create(['donor_id' => $crossoverDonor->id, 'campaign_id' => $demo->id]);
    $keptDonation = Donation::factory()->succeeded()->create(['donor_id' => $crossoverDonor->id, 'campaign_id' => $real->id]);

    Artisan::call('demo:purge', ['--no-interaction' => true]);

    expect(Donor::find($crossoverDonor->id))->not->toBeNull()
        ->and(Donation::find($keptDonation->id))->not->toBeNull()
        ->and(Donation::where('campaign_id', $demo->id)->count())->toBe(0);
});

it('removes the seeded demo testimonials but not real ones', function () {
    Testimonial::factory()->create(['name' => 'Rohit Verma']);
    $real = Testimonial::factory()->create(['name' => 'A Real Supporter']);

    Artisan::call('demo:purge', ['--no-interaction' => true]);

    expect(Testimonial::where('name', 'Rohit Verma')->count())->toBe(0)
        ->and(Testimonial::find($real->id))->not->toBeNull();
});

it('deletes nothing on a dry run', function () {
    $demo = makeCampaign('safe-shelter-for-abandoned-infants');

    Artisan::call('demo:purge', ['--dry-run' => true, '--no-interaction' => true]);

    expect(Campaign::find($demo->id))->not->toBeNull();
});

it('refuses to purge a demo campaign that has catalogue products', function () {
    $demo = makeCampaign('library-and-digital-lab-govt-school');
    $demo->products()->create([
        'name' => 'Textbook set',
        'unit_price' => 50000,
        'units_needed' => 10,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $exit = Artisan::call('demo:purge', ['--no-interaction' => true]);

    // Reports and stops, rather than blowing up mid-transaction on restrictOnDelete.
    expect($exit)->toBe(1)
        ->and(Campaign::find($demo->id))->not->toBeNull();
});
