<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\ImpactStat;
use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;

/**
 * Reverses DemoCampaignSeeder — the client-walkthrough content (8 fictional campaigns,
 * the Faker donors and donations backing their counters, 3 invented testimonials) that
 * shipped so a live demo wouldn't show empty states.
 *
 * Deletes by the seeder's own slugs/labels rather than truncating tables, so a real
 * campaign added alongside the demo set is never caught in the blast radius. Anything
 * not in DemoCampaignSeeder is left strictly alone.
 *
 * Ordering matters. `campaign_products.campaign_id` is `restrictOnDelete`, so a campaign
 * carrying catalogue items refuses to be force-deleted — the command reports those rather
 * than half-completing. `donations.campaign_id` has no FK at all (deferred at migration
 * time), so donations do NOT cascade from the campaign and must go explicitly, or they
 * survive as orphans still counted by the reconciler.
 *
 * Run with --dry-run first; it prints the same report without writing.
 */
class PurgeDemoContent extends Command
{
    use ConfirmableTrait;

    protected $signature = 'demo:purge
                            {--dry-run : Report what would be deleted without deleting it}
                            {--stats : Also remove the placeholder impact stats}
                            {--force : Skip the production confirmation prompt}';

    protected $description = 'Removes DemoCampaignSeeder content (demo campaigns, their donors/donations, demo testimonials)';

    /**
     * Mirrors DemoCampaignSeeder::campaignData()'s slugs. Kept as a literal list because
     * the seeder is presentation content that may be edited or deleted later — deriving
     * this from the seeder class would make the purge silently incomplete the moment
     * someone trims it.
     *
     * @var list<string>
     */
    private const DEMO_CAMPAIGN_SLUGS = [
        'winter-blankets-for-street-families',
        'scholarships-for-first-generation-learners',
        'life-saving-surgery-for-arjun',
        'community-kitchen-daily-meals',
        'dignified-care-for-elders-sunrise-home',
        'safe-shelter-for-abandoned-infants',
        'flood-relief-kits-assam',
        'library-and-digital-lab-govt-school',
    ];

    /** @var list<string> */
    private const DEMO_TESTIMONIAL_NAMES = ['Rohit Verma', 'Priya Nair', 'Sanjay Iyer'];

    /** @var list<string> */
    private const DEMO_STAT_LABELS = ['Meals Served', 'Students Supported', 'Families Reached', 'Verified Campaigns'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $campaigns = Campaign::query()
            ->withTrashed()
            ->whereIn('slug', self::DEMO_CAMPAIGN_SLUGS)
            ->get();

        // Testimonials and stats are seeded independently of the campaigns, so "no demo
        // campaigns" does not mean "nothing to purge" — bailing on the campaign count
        // alone silently left the fabricated testimonials on the site.
        $hasTestimonials = Testimonial::query()->whereIn('name', self::DEMO_TESTIMONIAL_NAMES)->exists();

        if ($campaigns->isEmpty() && ! $hasTestimonials && ! $this->option('stats')) {
            $this->info('Nothing to purge — no demo content found.');

            return self::SUCCESS;
        }

        $campaignIds = $campaigns->pluck('id')->all();

        // Guard: a campaign with catalogue items cannot be force-deleted (restrictOnDelete).
        // Surface it instead of letting the transaction blow up halfway through.
        $blocked = $campaigns->filter(fn (Campaign $c) => $c->products()->exists());

        if ($blocked->isNotEmpty()) {
            $this->error('These demo campaigns have catalogue products and cannot be removed automatically:');
            $blocked->each(fn (Campaign $c) => $this->line("  - {$c->slug}"));
            $this->line('Delete their products first, or keep the campaigns.');

            return self::FAILURE;
        }

        $donorIds = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->pluck('donor_id')
            ->unique()
            ->all();

        // Only donors whose ENTIRE giving history is demo campaigns. A donor who also gave
        // to a real campaign is a real donor and must survive — this is the check that
        // keeps the purge safe to run after go-live, not just before it.
        $donorIdsToDelete = empty($donorIds) ? [] : Donor::query()
            ->whereIn('id', $donorIds)
            ->whereDoesntHave('donations', fn ($q) => $q->whereNotIn('campaign_id', $campaignIds))
            ->pluck('id')
            ->all();

        $donationCount = Donation::query()->whereIn('campaign_id', $campaignIds)->count();
        $testimonials = Testimonial::query()->whereIn('name', self::DEMO_TESTIMONIAL_NAMES);
        $testimonialCount = $testimonials->count();

        $this->table(['What', 'Count'], [
            ['Demo campaigns', $campaigns->count()],
            ['Donations on them', $donationCount],
            ['Donors (demo-only)', count($donorIdsToDelete)],
            ['Donors kept (also gave elsewhere)', count($donorIds) - count($donorIdsToDelete)],
            ['Demo testimonials', $testimonialCount],
            ['Impact stats', $this->option('stats') ? count(self::DEMO_STAT_LABELS) : 0],
        ]);

        if ($dryRun) {
            $this->comment('Dry run — nothing was deleted.');

            return self::SUCCESS;
        }

        // Laravel's standard destructive-command guard: prompts in production, honours
        // --force, and stays quiet in tests. Rolling our own here is how you end up
        // calling a method that doesn't exist on OutputStyle.
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($campaignIds, $donorIdsToDelete, $campaigns) {
            // Donations first: no FK on campaign_id means nothing cascades them away.
            Donation::query()->whereIn('campaign_id', $campaignIds)->delete();

            if ($donorIdsToDelete !== []) {
                Donor::query()->whereIn('id', $donorIdsToDelete)->delete();
            }

            Testimonial::query()->whereIn('name', self::DEMO_TESTIMONIAL_NAMES)->delete();

            if ($this->option('stats')) {
                ImpactStat::query()->whereIn('label', self::DEMO_STAT_LABELS)->delete();
            }

            // forceDelete, not delete — Campaign soft-deletes, and a soft-deleted demo
            // campaign still occupies its slug and still answers the reconciler.
            $campaigns->each(fn (Campaign $c) => $c->forceDelete());
        });

        $this->info('Demo content purged.');

        return self::SUCCESS;
    }
}
