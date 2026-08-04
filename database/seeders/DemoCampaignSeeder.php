<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\ImpactStat;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Client-demo content only — real, readable English copy (not Faker lorem
 * ipsum) so a remote client walkthrough of the live site doesn't show
 * "Rem corporis eos labore" as a campaign title. Safe to run more than once:
 * every row is `firstOrCreate`'d against its slug/label, so re-running this
 * (e.g. after a fresh deploy) never duplicates data.
 *
 * NOT wired into DatabaseSeeder — this is presentation content, not the
 * baseline the app needs to function, so it stays a deliberate, one-off
 * `php artisan db:seed --class=DemoCampaignSeeder` rather than something
 * that runs on every fresh install.
 *
 * Money fields (`goal_amount`, `raised_amount`, `offline_raised_amount`) are
 * stored in paise per App\Support\Money's convention — every amount below is
 * written as `<rupees> * 100` rather than a hand-converted paise literal, so
 * the actual rupee figure stays readable and the conversion can't drift.
 */
class DemoCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $this->campaigns();
        $this->impactStats();
        $this->testimonials();
    }

    private function campaigns(): void
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
                'slug' => 'winter-blankets-for-street-families',
                'category_slug' => 'cloth-distribution',
                'title' => 'Winter Blankets for Street Families',
                'subtitle' => 'Help us reach 500 families sleeping rough before the cold sets in.',
                'beneficiary_name' => 'Shanti Night Shelter Collective',
                'story' => "Every winter, families with nowhere else to sleep face nights that drop below 8°C on Delhi's pavements and under its flyovers. Last year, three volunteers from our shelter collective found a mother and two children sharing a single thin shawl through the coldest week of December.\n\nThis campaign funds thick wool blankets, warm socks, and basic winter clothing for families identified through our nightly outreach rounds. Each blanket costs approximately ₹450 fully delivered, and every distribution is documented and photographed so donors can see exactly where their contribution went.\n\nWe are aiming to reach 500 families before the first cold wave arrives. Your support — whether it funds one blanket or fifty — goes directly to someone who will feel warmer tonight.",
                'goal_amount' => 450_000 * 100,
                'raised_amount' => 312_000 * 100,
                'donor_count' => 214,
                'offline_raised_amount' => 0,
                'allows_recurring' => true,
                'is_tax_benefit' => true,
                'is_featured' => true,
                'is_urgent' => true,
                'status' => 'active',
                'starts_at' => now()->subDays(18),
                'meta_title' => 'Winter Blankets for Street Families — Donate Now',
                'meta_description' => 'Fund warm blankets and winter clothing for families sleeping without shelter this winter. Every contribution is 80G tax-deductible.',
            ],
            [
                'slug' => 'scholarships-for-first-generation-learners',
                'category_slug' => 'education-support',
                'title' => 'Scholarships for First-Generation Learners',
                'subtitle' => 'Full-year tuition support for 40 students who would otherwise drop out.',
                'beneficiary_name' => 'Asha Vidya Foundation',
                'story' => "Meera is the first person in her family to reach the tenth grade. Her father drives an auto-rickshaw, and after his engine repair bill last month, the family could no longer set aside money for her school fees, uniform, and books.\n\nMeera is one of forty students this campaign supports — all first-generation learners at government and low-fee schools whose families are one unexpected expense away from pulling them out of school entirely. A single scholarship of ₹18,000 covers a full academic year: tuition, textbooks, a uniform, and transport.\n\nWe verify each student's enrolment directly with their school and publish a progress update every term — attendance records, report cards, and photos from school visits — so you can see the difference your support makes, not just read about it.",
                'goal_amount' => 720_000 * 100,
                'raised_amount' => 486_500 * 100,
                'donor_count' => 356,
                'offline_raised_amount' => 50_000 * 100,
                'allows_recurring' => true,
                'is_tax_benefit' => true,
                'is_featured' => true,
                'is_urgent' => false,
                'status' => 'active',
                'starts_at' => now()->subDays(64),
                'meta_title' => 'Scholarships for First-Generation Learners — Donate Now',
                'meta_description' => 'Fund a full year of school fees, books and uniforms for a first-generation learner. 80G tax-deductible, with termly progress updates.',
            ],
            [
                'slug' => 'life-saving-surgery-for-arjun',
                'category_slug' => 'medical-facilities',
                'title' => 'Life-Saving Surgery for Arjun, Age 6',
                'subtitle' => 'A congenital heart defect needs urgent surgery his family cannot afford.',
                'beneficiary_name' => 'Arjun Patil',
                'story' => "Arjun was born with a hole in his heart. For six years his family has managed his condition with medication, but his cardiologist at the district hospital has now confirmed that without corrective surgery within the next two months, the risk to his health will increase sharply.\n\nThe surgery, at a partner hospital that has agreed to a subsidised rate for verified campaigns on this platform, costs ₹3,80,000 — several times his father's annual income as a farm labourer. The family has already sold what little land they owned to cover diagnostic tests.\n\nWe have verified Arjun's medical reports with the treating cardiologist directly, and funds raised go straight to the hospital, not to the family, so every rupee is accounted for. An update with his discharge summary will be published here the moment the surgery is complete.",
                'goal_amount' => 380_000 * 100,
                'raised_amount' => 0,
                'donor_count' => 0,
                'offline_raised_amount' => 0,
                'allows_recurring' => false,
                'is_tax_benefit' => true,
                'is_featured' => true,
                'is_urgent' => true,
                'status' => 'active',
                'starts_at' => now()->subDays(6),
                'meta_title' => 'Life-Saving Heart Surgery for Arjun, Age 6 — Donate Now',
                'meta_description' => 'Arjun needs urgent corrective heart surgery his family cannot afford. Verified medical case, funds paid directly to the hospital.',
            ],
            [
                'slug' => 'community-kitchen-daily-meals',
                'category_slug' => 'food-donation',
                'title' => 'Keep Our Community Kitchen Serving Daily Meals',
                'subtitle' => 'Sustaining 300 hot meals a day for daily-wage families and the elderly.',
                'beneficiary_name' => 'Anna Seva Community Kitchen',
                'story' => "Our community kitchen has served hot, nutritious meals to daily-wage workers, the elderly, and families between jobs every single day for the past three years — but rising grocery prices mean our monthly running costs have outgrown our regular donor base.\n\nEach meal costs approximately ₹35 to prepare and serve, covering ingredients, cooking gas, and the wages of the four cooks who run the kitchen. We currently serve around 300 meals a day, seven days a week, rain or shine.\n\nThis campaign funds one month of uninterrupted service. We publish a short weekly update — meals served, any new families reached — so every donor can see the kitchen stay open because of their support.",
                'goal_amount' => 315_000 * 100,
                'raised_amount' => 189_500 * 100,
                'donor_count' => 142,
                'offline_raised_amount' => 20_000 * 100,
                'allows_recurring' => true,
                'is_tax_benefit' => true,
                'is_featured' => false,
                'is_urgent' => false,
                'status' => 'active',
                'starts_at' => now()->subDays(40),
                'meta_title' => 'Keep Our Community Kitchen Serving Daily Meals — Donate Now',
                'meta_description' => 'Fund a month of hot daily meals for daily-wage families and elders through our community kitchen. 80G tax-deductible.',
            ],
            [
                'slug' => 'dignified-care-for-elders-sunrise-home',
                'category_slug' => 'old-age-home-support',
                'title' => 'Dignified Care for Elders at Sunrise Home',
                'subtitle' => 'Medicines, bedding and staff wages for 35 residents with no family support.',
                'beneficiary_name' => 'Sunrise Old Age Home',
                'story' => "Sunrise Home is currently caring for 35 residents, several of whom arrived with no family able or willing to support them. Running the home costs roughly ₹2,10,000 a month — medicines, food, bedding, and wages for the small staff who provide round-the-clock care.\n\nA recent health camp identified two residents who need ongoing physiotherapy after falls, and three whose diabetes medication needs are currently being stretched further than they should be. This campaign funds a full month of operating costs so no resident's care has to be rationed.\n\nWe publish photos from festivals, health camp results, and general updates from the home so donors can see the day-to-day dignity their support makes possible.",
                'goal_amount' => 210_000 * 100,
                'raised_amount' => 96_500 * 100,
                'donor_count' => 88,
                'offline_raised_amount' => 0,
                'allows_recurring' => true,
                'is_tax_benefit' => true,
                'is_featured' => false,
                'is_urgent' => false,
                'status' => 'active',
                'starts_at' => now()->subDays(22),
                'meta_title' => 'Dignified Care for Elders at Sunrise Home — Donate Now',
                'meta_description' => 'Fund medicines, bedding and staff wages for 35 elderly residents with no family support. 80G tax-deductible.',
            ],
            [
                'slug' => 'safe-shelter-for-abandoned-infants',
                'category_slug' => 'child-welfare',
                'title' => 'Safe Shelter and Nutrition for Abandoned Infants',
                'subtitle' => 'Round-the-clock care and therapeutic nutrition for 12 infants currently in our care.',
                'beneficiary_name' => "Chhoti Khushi Children's Shelter",
                'story' => "Our shelter is currently caring for twelve infants and toddlers, most of whom arrived malnourished or in fragile health. Several need therapeutic nutrition supplements alongside regular meals to recover safely, and all require round-the-clock caregiving staff.\n\nThis campaign funds a full month of formula, therapeutic nutrition, diapers, and caregiver wages. Every child in our care is registered with the local child welfare committee, and we publish growth-chart updates (without identifying photos, to protect the children's privacy) so donors can see real, measurable recovery.\n\nA single contribution of ₹2,500 covers a full month of nutrition for one infant. Every rupee here goes toward keeping vulnerable children safe, fed, and cared for.",
                'goal_amount' => 150_000 * 100,
                'raised_amount' => 104_000 * 100,
                'donor_count' => 176,
                'offline_raised_amount' => 0,
                'allows_recurring' => true,
                'is_tax_benefit' => true,
                'is_featured' => false,
                'is_urgent' => false,
                'status' => 'active',
                'starts_at' => now()->subDays(50),
                'meta_title' => 'Safe Shelter and Nutrition for Abandoned Infants — Donate Now',
                'meta_description' => 'Fund round-the-clock care and therapeutic nutrition for abandoned infants in our shelter. 80G tax-deductible.',
            ],
            [
                'slug' => 'flood-relief-kits-assam',
                'category_slug' => 'food-donation',
                'title' => 'Emergency Flood Relief Kits for Assam',
                'subtitle' => 'Dry rations, drinking water and tarpaulin sheets for displaced families.',
                'beneficiary_name' => 'Northeast Disaster Response Network',
                'story' => "Seasonal floods have displaced over 2,000 families in low-lying villages this month, many now sheltering on embankments with no access to clean drinking water or dry food. Our response teams are on the ground distributing emergency kits, but supplies are running low.\n\nEach relief kit — costing ₹850 — contains rice, lentils, cooking oil, water purification tablets, and a tarpaulin sheet for temporary shelter. We are aiming to distribute 1,000 kits over the next two weeks as water levels are expected to remain high.\n\nBecause this is an active emergency response, updates are posted directly from the field as distributions happen, often within hours of a kit reaching a family.",
                'goal_amount' => 850_000 * 100,
                'raised_amount' => 595_000 * 100,
                'donor_count' => 401,
                'offline_raised_amount' => 40_000 * 100,
                'allows_recurring' => false,
                'is_tax_benefit' => true,
                'is_featured' => true,
                'is_urgent' => true,
                'status' => 'active',
                'starts_at' => now()->subDays(3),
                'meta_title' => 'Emergency Flood Relief Kits for Assam — Donate Now',
                'meta_description' => 'Fund emergency dry ration kits, drinking water and shelter sheets for families displaced by flooding in Assam.',
            ],
            [
                'slug' => 'library-and-digital-lab-govt-school',
                'category_slug' => 'education-support',
                'title' => 'A Library and Digital Learning Lab for a Government School',
                'subtitle' => 'Successfully funded — 620 students now have access to books and computers.',
                'beneficiary_name' => 'Zilla Parishad Higher Secondary School',
                'story' => "This government school of 620 students had no library and no computers until this campaign. Thanks to 289 donors, we fully funded a stocked library with over 800 books and a digital learning lab with twelve computers, completed and handed over last month.\n\nTeachers report that student engagement in reading periods has visibly increased, and the digital lab now runs basic computer literacy classes for grades 6 through 10 — a first for this school.\n\nThank you to everyone who contributed. This campaign is now closed, but you can see exactly what your donation built.",
                'goal_amount' => 420_000 * 100,
                'raised_amount' => 420_000 * 100,
                'donor_count' => 289,
                'offline_raised_amount' => 0,
                'allows_recurring' => false,
                'is_tax_benefit' => true,
                'is_featured' => false,
                'is_urgent' => false,
                'status' => 'completed',
                'starts_at' => now()->subMonths(4),
                'ends_at' => now()->subDays(25),
                'meta_title' => 'A Library and Digital Learning Lab for a Government School',
                'meta_description' => 'Fully funded: a library and digital learning lab built for a government school of 620 students.',
            ],
        ];
    }

    private function impactStats(): void
    {
        foreach ([
            ['label' => 'Meals Served', 'value' => '48,500', 'suffix' => '+', 'sort_order' => 1],
            ['label' => 'Students Supported', 'value' => '1,240', 'suffix' => '+', 'sort_order' => 2],
            ['label' => 'Families Reached', 'value' => '6,800', 'suffix' => '+', 'sort_order' => 3],
            ['label' => 'Verified Campaigns', 'value' => '95', 'suffix' => '+', 'sort_order' => 4],
        ] as $stat) {
            ImpactStat::query()->firstOrCreate(['label' => $stat['label']], $stat);
        }
    }

    private function testimonials(): void
    {
        foreach ([
            [
                'name' => 'Rohit Verma',
                'location' => 'Pune, Maharashtra',
                'quote' => "I donated toward Arjun's surgery campaign and got a call from the coordinator within a day confirming receipt — that level of transparency is rare. I'll be donating again.",
            ],
            [
                'name' => 'Priya Nair',
                'location' => 'Bengaluru, Karnataka',
                'quote' => 'The termly updates on the scholarship students I supported meant I could actually see the difference — report cards, photos, real progress. Not just a thank-you email.',
            ],
            [
                'name' => 'Sanjay Iyer',
                'location' => 'Chennai, Tamil Nadu',
                'quote' => 'Setting up a monthly donation took two minutes, and the 80G receipt arrived instantly. Exactly what I look for before trusting an NGO with recurring payments.',
            ],
        ] as $sortOrder => $testimonial) {
            Testimonial::query()->firstOrCreate(
                ['name' => $testimonial['name'], 'quote' => $testimonial['quote']],
                [...$testimonial, 'sort_order' => $sortOrder, 'is_published' => true]
            );
        }
    }
}
