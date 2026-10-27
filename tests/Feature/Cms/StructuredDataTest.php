<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\CampaignFaq;
use App\Models\Page;
use Database\Seeders\CampaignFaqSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\DB;

/*
 * Blade treats a literal `@context` as one of its own directives. Inside the schema.org JSON-LD it
 * compiled to PHP source and left raw code in the page's <script type="application/ld+json">, so
 * every block on the site was invalid JSON and search engines could read none of it. Templates now
 * write `@@context`; this asserts what a crawler actually receives.
 */

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

/** @return list<array<string, mixed>> */
function jsonLdBlocks(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

    return array_map(fn (string $raw) => json_decode(trim($raw), true), $m[1]);
}

it('emits valid schema.org JSON-LD on every public page', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();
    $blocks = jsonLdBlocks($html);

    expect($blocks)->not->toBeEmpty()
        ->and($html)->not->toContain('__contextArgs');

    foreach ($blocks as $block) {
        expect($block)->toBeArray()->and($block['@context'] ?? null)->toBe('https://schema.org');
    }
})->with(['/', '/donate', '/campaigns', '/blog', '/certificates', '/contact']);

it('emits valid JSON-LD on a CMS page and on a campaign page including its FAQ', function () {
    $this->seed(CampaignFaqSeeder::class);
    Page::factory()->create(['slug' => 'about', 'title' => 'About']);
    $campaign = Campaign::factory()->for(CampaignCategory::factory(), 'category')->create(['status' => 'active']);

    foreach (['/about', "/campaigns/{$campaign->slug}"] as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        $blocks = jsonLdBlocks($html);

        expect($blocks)->not->toBeEmpty()->and($html)->not->toContain('__contextArgs');

        foreach ($blocks as $block) {
            expect($block)->toBeArray()->and($block['@context'])->toBe('https://schema.org');
        }
    }
});

// --- FAQ tax answers -----------------------------------------------------------------------------

it('does not tell donors their gift is 80G-deductible before 80G exists', function () {
    $this->seed(CampaignFaqSeeder::class);
    $campaign = Campaign::factory()->for(CampaignCategory::factory(), 'category')->create(['status' => 'active']);

    $html = $this->get("/campaigns/{$campaign->slug}")->assertOk()->getContent();

    expect($html)
        ->toContain('Not at the moment')
        ->not->toContain('are eligible for deduction under Section 80G')
        ->not->toContain('Section 80G tax-exemption certificate');
});

function runFaqMigration(string $direction = 'up'): void
{
    (require database_path('migrations/2026_10_27_000001_make_global_faq_tax_answers_truthful.php'))->{$direction}();
}

it('rewrites a global FAQ that still has the original 80G text, and leaves an edited one alone', function () {
    $q1 = 'Do I get a tax benefit for donating?';
    $original = 'Yes. Donations to campaigns marked with the "Tax Benefit" badge are eligible for deduction under Section 80G of the Income Tax Act, 1961, subject to the limits and conditions specified therein. An 80G receipt is generated automatically once you provide your PAN and address.';

    CampaignFaq::query()->create(['campaign_id' => null, 'question' => $q1, 'answer' => $original, 'sort_order' => 0, 'is_published' => true]);
    CampaignFaq::query()->create(['campaign_id' => null, 'question' => 'Can I donate anonymously?', 'answer' => 'An answer an admin wrote themselves.', 'sort_order' => 1, 'is_published' => true]);

    runFaqMigration();

    expect(DB::table('campaign_faqs')->where('question', $q1)->value('answer'))->toStartWith('Not at the moment.')
        ->and(DB::table('campaign_faqs')->where('question', 'Can I donate anonymously?')->value('answer'))->toBe('An answer an admin wrote themselves.');

    runFaqMigration('down');

    expect(DB::table('campaign_faqs')->where('question', $q1)->value('answer'))->toBe($original);
});
