<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Three global FAQs, shown on every campaign page, told donors their gifts are 80G-deductible and
 * that an 80G certificate would be issued. The organisation is a Section 8 company with no 12A/80G
 * registration yet, so a donor could rely on a deduction they cannot claim.
 *
 * Each row is rewritten ONLY if it still holds the exact original seeded text, so an answer an
 * admin has since edited is left alone. When 80G is granted, restore the tax wording from the
 * admin (Campaign FAQs) — the site's other 80G claims return by themselves, but FAQ text is
 * ordinary editable content and does not.
 */
return new class extends Migration
{
    /** @return list<array{question: string, old: string, new: string}> */
    private function rewrites(): array
    {
        return [
            [
                'question' => 'Do I get a tax benefit for donating?',
                'old' => 'Yes. Donations to campaigns marked with the "Tax Benefit" badge are eligible for deduction under Section 80G of the Income Tax Act, 1961, subject to the limits and conditions specified therein. An 80G receipt is generated automatically once you provide your PAN and address.',
                'new' => 'Not at the moment. Vision Good Work Global Foundation is a registered Section 8 non-profit company, but our 80G tax-exemption registration is not yet in place, so donations are not currently eligible for an income-tax deduction. We will update this answer as soon as that changes.',
            ],
            [
                'question' => 'Can I donate anonymously?',
                'old' => 'Yes. Check "Donate anonymously" at checkout and your name will never be shown on the public donor wall or in any published material. Note that an 80G tax receipt requires your name and PAN, so it cannot be issued for an anonymous donation.',
                'new' => 'Yes. Check "Donate anonymously" at checkout and your name will never be shown on the public donor wall or in any published material.',
            ],
            [
                'question' => 'Will I get a receipt for my donation?',
                'old' => 'Yes. Every successful donation receives an acknowledgement receipt by email immediately. If you have provided your PAN and address and the donation qualifies, you will also receive a Section 80G tax-exemption certificate.',
                'new' => 'Yes. Every successful donation receives an acknowledgement receipt by email.',
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->rewrites() as $r) {
            DB::table('campaign_faqs')
                ->whereNull('campaign_id')
                ->where('question', $r['question'])
                ->where('answer', $r['old'])
                ->update(['answer' => $r['new'], 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach ($this->rewrites() as $r) {
            DB::table('campaign_faqs')
                ->whereNull('campaign_id')
                ->where('question', $r['question'])
                ->where('answer', $r['new'])
                ->update(['answer' => $r['old'], 'updated_at' => now()]);
        }
    }
};
