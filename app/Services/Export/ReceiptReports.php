<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\ReceiptSequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Two reports docs/modules/M07-receipts-80g.md calls for explicitly:
 *
 * - The **gap-detection report** — "should always be empty; if it isn't,
 *   something is seriously wrong." A cancelled receipt's number is not a
 *   gap (the number is retained, never reused) — only a genuinely missing
 *   sequence_number between 1 and the sequence's own last_number is.
 * - The **FY receipt register** — totals per series per financial year, for
 *   the admin Reports section.
 */
final class ReceiptReports
{
    /**
     * @return list<int> missing sequence numbers, empty when the sequence is gap-free
     */
    public function gaps(string $series, string $financialYear): array
    {
        $sequence = ReceiptSequence::query()
            ->where('series', $series)
            ->where('financial_year', $financialYear)
            ->first();

        if (! $sequence || $sequence->last_number === 0) {
            return [];
        }

        $existing = DB::table('receipts')
            ->where('series', $series)
            ->where('financial_year', $financialYear)
            ->pluck('sequence_number')
            ->all();

        $expected = range(1, $sequence->last_number);

        return array_values(array_diff($expected, $existing));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function register(): Collection
    {
        $rows = DB::table('receipts')
            ->selectRaw('series, financial_year, count(*) as count, sum(amount) as total, sum(case when is_cancelled then 1 else 0 end) as cancelled_count')
            ->groupBy('series', 'financial_year')
            ->orderByDesc('financial_year')
            ->orderBy('series')
            ->get();

        $result = new Collection;

        foreach ($rows as $row) {
            $result->push($this->toRegisterRow($row));
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function toRegisterRow(\stdClass $row): array
    {
        return [
            'series' => $row->series,
            'financial_year' => $row->financial_year,
            'count' => (int) $row->count,
            'total' => (int) $row->total,
            'cancelled_count' => (int) $row->cancelled_count,
        ];
    }
}
