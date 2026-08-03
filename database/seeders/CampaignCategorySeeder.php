<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CampaignCategory;
use Illuminate\Database\Seeder;

/**
 * The six browse-by-cause tiles this organisation actually runs programmes for —
 * replaces an earlier eight-category placeholder set. `intro_body` copy below is
 * genuine placeholder prose (150+ words each) written to ship a working, indexable
 * `/causes/{slug}` page on day one — the client is expected to refine the wording
 * later, but the page must never be blank. See docs/07-SEO.md §1.
 */
class CampaignCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $sortOrder => $category) {
            CampaignCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'sort_order' => $sortOrder, 'is_active' => true]
            );
        }
    }

    /**
     * @return list<array{name: string, slug: string, description: string, intro_body: string}>
     */
    private function categories(): array
    {
        return [
            [
                'name' => 'Food Donation',
                'slug' => 'food-donation',
                'description' => 'Nutritious meals for the hungry and families facing food insecurity.',
                'intro_body' => "Hunger remains a daily reality for millions of families across India, particularly in communities where a single lost wage can mean skipped meals for days. Campaigns in this cause fund community kitchens, ration kits, and daily meal programmes for the elderly, the homeless, and families who cannot reliably put food on the table. A donation for food relief might pay for a week of hot meals served at a shelter, a month's grocery kit delivered to a family between jobs, or emergency food supplies rushed to a community after a disaster. Every campaign here is run by a verified NGO or community kitchen with an established distribution network, so contributions reach people quickly rather than sitting in a warehouse. Updates are published as meals are served and kits are distributed, giving donors a clear, ongoing picture of where their support went — not just a one-time thank-you. Every donation toward food relief is eligible for an 80G tax deduction, and no contribution is too small: even a modest amount can fund several full meals for someone who would otherwise go without.",
            ],
            [
                'name' => 'Old Age Home Support',
                'slug' => 'old-age-home-support',
                'description' => 'Care, shelter and companionship for elders without family support.',
                'intro_body' => 'Many elderly citizens in India spend their later years without family nearby, reliable income, or easy access to the medical care their age demands. Campaigns in this cause support old-age homes and elder-care programmes directly — funding daily meals, medicines, bedding, staff salaries and the everyday running costs that keep a shelter open for residents who have nowhere else to go. A donation here might cover a month of medicines for an elder recovering from a fall, a mattress and blanket for someone who arrived with nothing, or a share of the monthly costs that keep an entire home operating. Every campaign is run by a verified old-age home or elder-care organisation, and each publishes regular updates — a renovated ward, a health camp completed, residents celebrating a festival together — so donors can see exactly how their support translated into a safer, more dignified daily life for the people it reached. Every contribution toward elder care is eligible for an 80G tax deduction, and supporting this cause means giving people in their later years the security and dignity that too many are otherwise left without.',
            ],
            [
                'name' => 'Child Welfare',
                'slug' => 'child-welfare',
                'description' => 'Nutrition, protection and a safer childhood for at-risk children.',
                'intro_body' => "Millions of children across India grow up without reliable access to nutritious food, safe housing, or protection from the risks that come with poverty. Campaigns in this cause fund shelter and daily care for abandoned and orphaned children, nutrition programmes for underweight and at-risk infants, and medical treatment for conditions families cannot otherwise afford. A donation toward child welfare might cover a month of care at a children's shelter, therapeutic nutrition for a malnourished infant, or emergency medical treatment that a family has no other way to pay for. The NGOs and care homes featured in this cause are verified before their campaigns go live, and each publishes real updates — photos, milestones, discharge reports — so donors can see precisely how a contribution changed a child's immediate circumstances, not just read a promise that it did. Every donation here carries a full 80G tax benefit, and because outcomes for at-risk children are measured in weeks and months rather than years, the impact of a single contribution is often visible faster than in almost any other cause on this platform.",
            ],
            [
                'name' => 'Education Support',
                'slug' => 'education-support',
                'description' => 'School fees, supplies and scholarships for students who cannot afford them.',
                'intro_body' => "Education remains the single most reliable path out of poverty, yet school fees, books, uniforms and transport costs put it out of reach for millions of families across India. Campaigns in this cause fund scholarships, school supplies, digital learning tools, and support for schools serving underprivileged communities. A donation toward education can pay a full year's tuition for a student who would otherwise have to drop out, or help stock a library or computer lab that an entire school currently goes without. Every fundraiser here is run by a verified NGO, school, or individual sponsor, with transparent updates showing exactly how contributions are used — a scholarship disbursed, supplies delivered, a term completed. Donors receive full visibility into outcomes rather than just intentions, and every contribution is eligible for an 80G tax deduction. Supporting education is one of the highest-leverage ways to give on this platform: a single donation can change the entire trajectory of a student's life, and the campaigns here are chosen specifically because that outcome can be tracked and shown, not just claimed.",
            ],
            [
                'name' => 'Medical Facilities',
                'slug' => 'medical-facilities',
                'description' => 'Treatment, medicines and healthcare access for those who cannot afford it.',
                'intro_body' => "For families without health insurance or savings to fall back on, a serious diagnosis can mean choosing between treatment and every other basic need. Campaigns in this cause fund surgeries, ongoing treatment for chronic conditions, medicines, diagnostic tests, and support for health camps that bring basic care to communities with no nearby clinic. A donation toward medical relief might fund a life-saving surgery a family could never have paid for alone, a course of medication for someone managing a chronic illness, or a single health camp that screens and treats an entire village in a day. Every campaign is verified against real medical documentation before it goes live, and updates are published as treatment progresses — a surgery completed, a patient discharged, a camp's results tallied — so donors can follow a contribution from donation to outcome. Every donation here is eligible for an 80G tax deduction, and because medical need is rarely something that can wait, campaigns in this cause are reviewed and published quickly so that funds can reach patients while treatment is still possible.",
            ],
            [
                'name' => 'Cloth Distribution',
                'slug' => 'cloth-distribution',
                'description' => 'Warm clothing and essentials for underprivileged families.',
                'intro_body' => 'Something as basic as a warm blanket or a new set of clothes is out of reach for many families living on the street or in extreme poverty, particularly through winter months when exposure becomes a genuine health risk. Campaigns in this cause fund the purchase and distribution of clothing, blankets and footwear to homeless individuals, slum communities and families displaced by disaster or hardship. A donation toward clothing relief might fund blankets for an entire shelter ahead of a cold spell, school uniforms for children whose families cannot afford them, or footwear for a community that has been going without. Every campaign is run by a verified NGO or community group with an established distribution effort on the ground, and updates are published as items are handed out — photos from a distribution drive, a count of families reached — so donors can see the direct, immediate difference a contribution made. Every donation here carries a full 80G tax benefit, and because the need is often seasonal and urgent, these campaigns are reviewed quickly to make sure support arrives before the weather does.',
            ],
        ];
    }
}
