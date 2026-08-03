<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Ships the three CMS pages the sitemap already promises (About, Privacy
 * Policy, Terms) with real placeholder copy, so the site never launches
 * with a blank or missing page. See docs/modules/M10-public-site-cms.md.
 */
class HomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $sortOrder => $page) {
            Page::query()->firstOrCreate(['slug' => $page['slug']], [...$page, 'sort_order' => $sortOrder]);
        }
    }

    /**
     * @return list<array{slug: string, title: string, body: string}>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'about',
                'title' => 'About Us',
                'body' => "<p>Vision Good Work Global Foundation is a registered NGO working across India to support verified, high-impact causes — from animal welfare and child education to disaster relief and elderly care.</p><p>Every campaign on this platform is handpicked and verified by our team before it goes live, and every donation is tracked with full transparency: you can see exactly how much has been raised, how many donors have contributed, and — for campaigns with a needs catalogue — exactly which items your donation funded.</p><p>We are registered under Section 12A and hold valid 80G tax-exemption status, meaning donations made through this platform are eligible for tax deduction under the Income Tax Act, 1961.</p><p><em>This is placeholder copy — the organisation's real history, mission and team details will be added by the client.</em></p>",
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'body' => "<p>This Privacy Policy explains how Vision Good Work Global Foundation collects, uses and protects the personal information of visitors and donors to this website.</p><p>We collect information you provide directly — such as your name, email, phone number and payment details when you make a donation — and use it solely to process your donation, issue receipts, and communicate with you about the causes you've supported. We never sell or share your personal information with third parties for marketing purposes.</p><p>Payment information is processed by our PCI-DSS compliant payment gateway; we never store your card details on our own servers.</p><p><em>This is placeholder copy — the organisation's legal counsel should review and finalise this policy before launch.</em></p>",
            ],
            [
                'slug' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'body' => "<p>By using this website and making a donation, you agree to the following terms.</p><p>Donations made through this platform are voluntary contributions to support the causes and campaigns listed. Once a donation is processed, it is generally non-refundable except at the organisation's discretion in cases of genuine error. Tax-exemption receipts under Section 80G are issued for eligible donations where the donor has provided a valid PAN and address.</p><p>All content on this website, including campaign stories and images, remains the property of Vision Good Work Global Foundation or its respective owners and may not be reproduced without permission.</p><p><em>This is placeholder copy — the organisation's legal counsel should review and finalise these terms before launch.</em></p>",
            ],
        ];
    }
}
