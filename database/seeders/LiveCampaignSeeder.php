<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use Illuminate\Database\Seeder;

/**
 * The organisation's first two REAL campaigns, adapted from their Facebook appeals
 * (2026-08-08). Unlike DemoCampaignSeeder — which `demo:purge` exists to delete — this is
 * genuine content and must never be purged.
 *
 * Idempotent: every row is `firstOrCreate`'d on its slug, so re-running after a deploy
 * never duplicates. It also means edits made later through the admin are NOT overwritten
 * by a re-run — the admin is the source of truth once a campaign exists.
 *
 * Money is in paise per App\Support\Money, written as `<rupees> * 100` so the rupee figure
 * stays readable.
 *
 * ⚠️ GOAL AMOUNTS ARE PLACEHOLDERS, set at the client's instruction to get these live.
 * Both must be replaced with real figures — for Dipanshu the treating hospital can quote
 * the actual cost of the relapse protocol. The goal is the number donors judge an appeal
 * on, and a wrong one is visible on every card as a wrong progress percentage.
 *
 * Cover images live in storage/app/public/campaigns/ and are uploaded directly to the
 * server, deliberately NOT committed: they are photographs of an identifiable sick child,
 * and git history is permanent even after a campaign closes and the family wants them
 * taken down. The family's Aadhaar card and Dipanshu's PET-CT reports were supplied as
 * verification evidence and are held offline only — publishing an Aadhaar number is
 * prohibited under the Aadhaar Act, and a child's diagnostic record is not campaign
 * material. See docs/15-CLIENT-FEEDBACK-REMEDIATION-PLAN.md.
 */
class LiveCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $categories = CampaignCategory::query()->pluck('id', 'slug');

        foreach ($this->campaignData() as $data) {
            $categorySlug = $data['category_slug'];
            unset($data['category_slug']);

            Campaign::query()->firstOrCreate(
                ['slug' => $data['slug']],
                [...$data, 'category_id' => $categories[$categorySlug] ?? $categories->first()]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function campaignData(): array
    {
        return [
            [
                'slug' => 'help-dipanshu-fight-cancer',
                'category_slug' => 'medical-facilities',
                'title' => 'Help Dipanshu Fight Cancer',
                'subtitle' => 'Nine years old, relapsed after 12 rounds of chemotherapy, and out of options his family can afford.',
                'beneficiary_name' => 'Dipanshu Raj',
                // HTML, not plain text — the story column holds Filament RichEditor output
                // and is rendered raw (sanitized) inside a `prose` block, so `\n\n` would
                // collapse into one run-on paragraph.
                'story' => <<<'STORY'
                    <p>Dipanshu is nine years old. He has been fighting Hodgkin lymphoma, a cancer of the lymphatic system, and he has already been through more treatment than most adults ever face &mdash; twelve full cycles of chemotherapy.</p>
                    <p>His most recent PET-CT scan brought the news his family had been dreading: the disease has come back, and it has spread further than before. He needs to begin a second, more intensive line of treatment, and he needs to begin it soon.</p>
                    <p>His family has already spent everything they have. His mother has been at his bedside through every round, and the cost of what comes next is simply beyond what they can raise on their own.</p>
                    <blockquote><p>&ldquo;I fold my hands and request you all &mdash; if it is possible for you, please help us. Even a small contribution from you can ease the path of his treatment.&rdquo;</p><p>&mdash; Dipanshu&rsquo;s mother</p></blockquote>
                    <p>We have seen Dipanshu&rsquo;s diagnostic reports and verified his treatment records directly. His case is being followed with the support of CANKIDS&ndash;KIDSCAN, a national organisation working on childhood cancer.</p>
                    <p>Every rupee raised here goes toward his treatment. If you are not able to give, sharing this appeal costs nothing &mdash; and it may reach someone who can.</p>
                    STORY,
                'cover_image_path' => 'campaigns/dipanshu-hospital.jpg',
                'cover_image_alt' => 'Dipanshu, nine years old, resting in a hospital bed with his mother sitting beside him',
                // PLACEHOLDER — replace with the hospital's quoted cost for the relapse protocol.
                'goal_amount' => 500_000 * 100,
                'allows_recurring' => false,
                'is_tax_benefit' => true,
                'is_featured' => true,
                'is_urgent' => true,
                'status' => 'active',
                'starts_at' => now(),
                'sort_order' => 1,
                'meta_title' => 'Help Dipanshu, 9, Fight Relapsed Hodgkin Lymphoma',
                'meta_description' => 'Dipanshu is nine and has relapsed after 12 cycles of chemotherapy. Help fund the next stage of his cancer treatment — 80G tax-exemption receipt issued instantly.',
            ],
            [
                'slug' => 'food-donation-drive',
                'category_slug' => 'food-donation',
                'title' => 'Food Donation Drive — One Plate at a Time',
                'subtitle' => 'Hot, nutritious meals for children and families who would otherwise go without.',
                'story' => <<<'STORY'
                    <p>One plate of food can be the reason for someone&rsquo;s smile.</p>
                    <p>Our food donation drive serves hot, freshly cooked meals to children and families who would otherwise go hungry. We run it where the need is most immediate &mdash; outside shelters, in low-income neighbourhoods, and wherever families tell us their children are going to bed without eating.</p>
                    <p>Your contribution translates directly into meals served. There is no minimum: a small amount feeds someone today, and a regular monthly gift lets us plan ahead and cook for more people at once.</p>
                    <p>Come and join us. Every plate counts.</p>
                    STORY,
                // No photograph available yet — the card and detail page both degrade to a
                // category placeholder rather than a broken image. Add a real distribution
                // photo through the admin as soon as one exists.
                'cover_image_path' => null,
                'cover_image_alt' => null,
                // PLACEHOLDER — replace once the real target is known. Ideally set it from
                // cost-per-meal x meals planned, so the figure is defensible.
                'goal_amount' => 100_000 * 100,
                'allows_recurring' => true,
                'is_tax_benefit' => true,
                'is_featured' => true,
                'is_urgent' => false,
                'status' => 'active',
                'starts_at' => now(),
                'sort_order' => 2,
                'meta_title' => 'Food Donation Drive — Feed a Child Today',
                'meta_description' => 'Help serve hot, nutritious meals to children and families facing hunger. Every contribution becomes meals served, with an instant 80G tax-exemption receipt.',
            ],
        ];
    }
}
