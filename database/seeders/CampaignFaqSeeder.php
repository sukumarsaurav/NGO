<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CampaignFaq;
use Illuminate\Database\Seeder;

/**
 * The six organisation-level questions shown on every campaign page — a
 * null `campaign_id` means global. See
 * docs/modules/M08-campaigns-crowdfunding.md's "Products — the needs
 * catalogue" section and docs/02-DATABASE-SCHEMA.md §8. Real `FAQPage`
 * structured data (docs/07-SEO.md §3) — genuine visible Q&A, not decoration.
 */
class CampaignFaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faqs() as $sortOrder => $faq) {
            CampaignFaq::query()->firstOrCreate(
                ['campaign_id' => null, 'question' => $faq['question']],
                ['answer' => $faq['answer'], 'sort_order' => $sortOrder, 'is_published' => true]
            );
        }
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function faqs(): array
    {
        return [
            [
                'question' => 'Do I get a tax benefit for donating?',
                'answer' => 'Yes. Donations to campaigns marked with the "Tax Benefit" badge are eligible for deduction under Section 80G of the Income Tax Act, 1961, subject to the limits and conditions specified therein. An 80G receipt is generated automatically once you provide your PAN and address.',
            ],
            [
                'question' => 'How do I know my donation is actually being used for the cause?',
                'answer' => 'Every campaign publishes regular updates with photos and outcomes as funds are used. You can also see the live progress bar, the donor count, and — where relevant — the needs-catalogue funding status for each item, all in real time.',
            ],
            [
                'question' => 'Is it safe to donate online here?',
                'answer' => 'Yes. All payments are processed through a secure, PCI-DSS compliant payment gateway supporting UPI, cards and net banking. We never store your card details, and every transaction is encrypted end to end.',
            ],
            [
                'question' => 'Can I donate anonymously?',
                'answer' => 'Yes. Check "Donate anonymously" at checkout and your name will never be shown on the public donor wall or in any published material. Note that an 80G tax receipt requires your name and PAN, so it cannot be issued for an anonymous donation.',
            ],
            [
                'question' => 'Can I set up a monthly donation instead of a one-time gift?',
                'answer' => 'Yes, for any campaign marked as accepting monthly giving. You can set up an automatic monthly donation via UPI Autopay, and pause or cancel it at any time from your donor portal.',
            ],
            [
                'question' => 'Will I get a receipt for my donation?',
                'answer' => 'Yes. Every successful donation receives an acknowledgement receipt by email immediately. If you have provided your PAN and address and the donation qualifies, you will also receive a Section 80G tax-exemption certificate.',
            ],
        ];
    }
}
