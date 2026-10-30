<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\SettingsSeeder;

/*
 * The public registration numbers — what lets a donor confirm the foundation is real. Shown on the
 * Certificates page and in the campaign page's "NGO Legal Verification" box from the same settings.
 */

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $settings = app(SettingsRepository::class);
    $settings->set('org.registration_number', 'U88900UP2026NPL252015');
    $settings->set('org.section8_licence_number', '191136');
    $settings->set('org.registration_date', '2026-08-31');
    $settings->set('org.tan', 'LKNV11797C');
    $settings->set('org.pan', 'AAMCV6427F');
});

it('shows the registration numbers on the Certificates page', function () {
    $this->get('/certificates')->assertOk()
        ->assertSee('Registration details')
        ->assertSee('U88900UP2026NPL252015')
        ->assertSee('191136')
        ->assertSee('31 Aug 2026')
        ->assertSee('LKNV11797C')
        ->assertSee('mca.gov.in');
});

it('shows them in the campaign page verification box', function () {
    $campaign = Campaign::factory()->for(CampaignCategory::factory(), 'category')->create(['status' => 'active']);

    $this->get("/campaigns/{$campaign->slug}")->assertOk()
        ->assertSee('NGO Legal Verification')
        ->assertSee('U88900UP2026NPL252015')
        ->assertSee('191136')
        ->assertSee('LKNV11797C');
});

it('keeps the PAN private until it is switched on', function () {
    $this->get('/certificates')->assertOk()->assertDontSee('AAMCV6427F');

    app(SettingsRepository::class)->set('org.pan_public', true);

    $this->get('/certificates')->assertOk()->assertSee('AAMCV6427F');
});

it('leaves out an identifier that has not been entered instead of showing a blank row', function () {
    app(SettingsRepository::class)->set('org.tan', null);
    app(SettingsRepository::class)->set('org.section8_licence_number', null);

    $this->get('/certificates')->assertOk()
        ->assertDontSee('TAN')
        ->assertDontSee('Section 8 Licence No.')
        ->assertSee('U88900UP2026NPL252015');
});

it('does not claim 12A or 80G among the registration details', function () {
    $this->get('/certificates')->assertOk()
        ->assertDontSee('12A')
        ->assertDontSee('80G');
});

// --- footer: the same numbers, on every page ---------------------------------------------------

it('lists every registration number in the footer of any page', function () {
    $this->get('/donate')->assertOk()
        ->assertSee('CIN: U88900UP2026NPL252015')
        ->assertSee('Section 8 Licence: 191136')
        ->assertSee('TAN: LKNV11797C')
        ->assertSee('View our certificates');
});

it('lists the PAN in the footer only when it is switched on', function () {
    $this->get('/donate')->assertOk()->assertDontSee('PAN: AAMCV6427F');

    app(SettingsRepository::class)->set('org.pan_public', true);

    $this->get('/donate')->assertOk()->assertSee('PAN: AAMCV6427F');
});

it('does not list 12A or 80G in the footer until those numbers exist', function () {
    $this->get('/donate')->assertOk()->assertDontSee('12A:')->assertDontSee('80G:');

    app(SettingsRepository::class)->set('org.12a_number', 'AB/12A/1');
    app(SettingsRepository::class)->set('org.80g_number', 'AB/80G/1');

    $this->get('/donate')->assertOk()->assertSee('12A: AB/12A/1')->assertSee('80G: AB/80G/1');
});
