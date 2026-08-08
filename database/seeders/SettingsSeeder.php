<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Every settings key the app will ever read, seeded once. SettingsRepository::set()
 * refuses to write a key that isn't here — this is the only place a new key gets
 * created. See docs/modules/M02-organisation-settings.md.
 *
 * Real values where known (org identity, receipt prefixes). Placeholders for
 * everything the client still has to supply: PAN, 80G number and validity,
 * registration numbers, signatory, social links, logos.
 */
class SettingsSeeder extends Seeder
{
    /** @var list<array{key: string, value: mixed, type: string, group: string, is_encrypted?: bool}> */
    private const SETTINGS = [
        // --- organisation ---------------------------------------------------
        ['key' => 'org.name', 'value' => 'Vision Good Work Global Foundation', 'type' => 'string', 'group' => 'organisation'],
        // Member code prefix, e.g. VGWGF-2026-00123. Not in the original M02
        // spec — added in Sprint 3 (M03) since MemberCodeGenerator needs it
        // and nothing about the org should be hard-coded (M02's own rule).
        ['key' => 'org.member_code_prefix', 'value' => 'VGWGF', 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.legal_name', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.tagline', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.logo', 'value' => null, 'type' => 'file', 'group' => 'organisation'],
        ['key' => 'org.logo_dark', 'value' => null, 'type' => 'file', 'group' => 'organisation'],
        ['key' => 'org.favicon', 'value' => null, 'type' => 'file', 'group' => 'organisation'],
        ['key' => 'org.address_line1', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.address_line2', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.city', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.state', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.pincode', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.email', 'value' => 'info@visiongoodworkglobalfoundation.org', 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.phone', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.whatsapp', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.registration_number', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.registration_date', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.pan', 'value' => null, 'type' => 'string', 'group' => 'organisation', 'is_encrypted' => true],
        ['key' => 'org.12a_number', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.80g_number', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.80g_valid_from', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.80g_valid_to', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        // NITI Aayog Darpan registration ID and audit-standard label shown in the campaign
        // page's "NGO Legal Verification" modal — previously hard-coded literals
        // (`AACTV1234F20231` and friends) in campaigns/show.blade.php. See
        // docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §5 and
        // docs/12-REMEDIATION-PLAN-HOME-CAMPAIGNS.md PR 5.1.
        ['key' => 'org.darpan_id', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.audit_standard', 'value' => 'Annual Public Financial Audit', 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.csr_number', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.authorised_signatory_name', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.authorised_signatory_designation', 'value' => null, 'type' => 'string', 'group' => 'organisation'],
        ['key' => 'org.signature_image', 'value' => null, 'type' => 'file', 'group' => 'organisation'],
        ['key' => 'org.seal_image', 'value' => null, 'type' => 'file', 'group' => 'organisation'],

        // --- donation --------------------------------------------------------
        ['key' => 'donation.min_amount', 'value' => 5000, 'type' => 'int', 'group' => 'donation'],
        ['key' => 'donation.preset_amounts', 'value' => [50000, 100000, 250000, 500000], 'type' => 'json', 'group' => 'donation'],
        ['key' => 'donation.currency', 'value' => 'INR', 'type' => 'string', 'group' => 'donation'],
        ['key' => 'donation.allow_anonymous', 'value' => true, 'type' => 'bool', 'group' => 'donation'],
        ['key' => 'donation.require_pan_above', 'value' => 5000000, 'type' => 'int', 'group' => 'donation'],
        ['key' => 'donation.cash_80g_limit', 'value' => 200000, 'type' => 'int', 'group' => 'donation'],

        // --- receipt -----------------------------------------------------------
        ['key' => 'receipt.prefix', 'value' => 'VGWGF/RCP/', 'type' => 'string', 'group' => 'receipt'],
        ['key' => 'receipt.80g_prefix', 'value' => 'VGWGF/80G/', 'type' => 'string', 'group' => 'receipt'],
        ['key' => 'receipt.number_padding', 'value' => 5, 'type' => 'int', 'group' => 'receipt'],
        ['key' => 'receipt.footer_note', 'value' => null, 'type' => 'string', 'group' => 'receipt'],
        ['key' => 'receipt.auto_email', 'value' => true, 'type' => 'bool', 'group' => 'receipt'],
        // Boilerplate deduction statement printed on every 80G receipt — see
        // docs/modules/M07-receipts-80g.md's "80G receipt contents" table.
        // Confirm the exact wording with the NGO's CA before launch.
        ['key' => 'receipt.80g_deduction_statement', 'value' => 'This donation is eligible for deduction under Section 80G of the Income Tax Act, 1961, subject to the limits and conditions specified therein.', 'type' => 'string', 'group' => 'receipt'],

        // --- social --------------------------------------------------------
        // Live profiles, supplied by the client 2026-08-08. Stored without the `igsh=` /
        // `si=` query parameters they arrived with — those are per-share referral tokens,
        // not part of the profile address.
        //
        // Facebook is the numeric-id form rather than the `/share/` link it was first given
        // as: the id is permanent and survives a rename, where a share link is a short-link
        // that can rot (and cost an extra redirect hop — the share link 302s to exactly
        // this id). Swap for a vanity URL if one is ever claimed; this will keep working
        // either way. Twitter/LinkedIn stay null — the client named only three networks,
        // and <x-social-links> drops any that are blank.
        ['key' => 'social.facebook', 'value' => 'https://www.facebook.com/profile.php?id=61584117746083', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social.instagram', 'value' => 'https://www.instagram.com/visiongoodworkglobal', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social.twitter', 'value' => null, 'type' => 'string', 'group' => 'social'],
        ['key' => 'social.linkedin', 'value' => null, 'type' => 'string', 'group' => 'social'],
        ['key' => 'social.youtube', 'value' => 'https://youtube.com/@visiongoodworkglobal', 'type' => 'string', 'group' => 'social'],

        // --- homepage — sections 6-8, docs/06-UI-UX-FOUNDATION.md §7 ---------
        ['key' => 'homepage.serve_heading', 'value' => null, 'type' => 'string', 'group' => 'homepage'],
        ['key' => 'homepage.serve_body', 'value' => null, 'type' => 'string', 'group' => 'homepage'],
        ['key' => 'homepage.serve_image', 'value' => null, 'type' => 'file', 'group' => 'homepage'],
        ['key' => 'homepage.monthly_heading', 'value' => null, 'type' => 'string', 'group' => 'homepage'],
        ['key' => 'homepage.monthly_body', 'value' => null, 'type' => 'string', 'group' => 'homepage'],
        ['key' => 'homepage.steps', 'value' => [], 'type' => 'json', 'group' => 'homepage'],
        ['key' => 'homepage.newsletter_heading', 'value' => null, 'type' => 'string', 'group' => 'homepage'],
        // Featured YouTube videos — embedded directly, which needs no API key, no access
        // token and no Meta/Google app review, unlike the Instagram feed. Admin pastes
        // ordinary watch/share URLs; <x-youtube-embed> extracts the id. See
        // docs/15-CLIENT-FEEDBACK-REMEDIATION-PLAN.md §3.
        ['key' => 'homepage.video_heading', 'value' => 'See our work', 'type' => 'string', 'group' => 'homepage'],
        ['key' => 'homepage.video_body', 'value' => null, 'type' => 'string', 'group' => 'homepage'],
        ['key' => 'homepage.videos', 'value' => [], 'type' => 'json', 'group' => 'homepage'],

        // --- seo -------------------------------------------------------------
        // Kept under 60 characters per docs/07-SEO.md §4 — the settings page
        // enforces this length, so the seeded value must actually comply.
        ['key' => 'seo.meta_title', 'value' => 'Vision Good Work Global Foundation | Donate Now', 'type' => 'string', 'group' => 'seo'],
        ['key' => 'seo.meta_description', 'value' => null, 'type' => 'string', 'group' => 'seo'],
        ['key' => 'seo.og_image', 'value' => null, 'type' => 'file', 'group' => 'seo'],
        ['key' => 'seo.google_analytics_id', 'value' => null, 'type' => 'string', 'group' => 'seo'],
        ['key' => 'seo.google_site_verification', 'value' => null, 'type' => 'string', 'group' => 'seo'],
        ['key' => 'seo.facebook_pixel_id', 'value' => null, 'type' => 'string', 'group' => 'seo'],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $definition) {
            $isEncrypted = $definition['is_encrypted'] ?? false;

            Setting::query()->firstOrCreate(
                ['key' => $definition['key']],
                [
                    'value' => $this->encode($definition['value'], $definition['type'], $isEncrypted),
                    'type' => $definition['type'],
                    'group' => $definition['group'],
                    'is_encrypted' => $isEncrypted,
                ]
            );
        }
    }

    private function encode(mixed $value, string $type, bool $isEncrypted): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = match ($type) {
            'json' => json_encode($value),
            'bool' => $value ? '1' : '0',
            default => (string) $value,
        };

        return $isEncrypted ? encrypt($raw) : $raw;
    }
}
