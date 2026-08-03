<?php

declare(strict_types=1);

namespace App\Services\Numbering;

use App\Enums\DocumentType;
use App\Models\DocumentNumberSequence;
use Illuminate\Database\ConnectionInterface;

/**
 * Produces `{PREFIX}-{YYYY}-{NNNN}`, e.g. `AL-2026-0042`. Sequence is per
 * (type, calendar year), row-locked — same two-step create-then-lock pattern
 * as MemberCodeGenerator and ReceiptNumberGenerator, and for the same reason:
 * `lockForUpdate()` on a row that doesn't exist yet locks nothing.
 */
final class DocumentNumberGenerator
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function next(DocumentType $type): string
    {
        $year = (int) now()->year;

        $this->ensureSequenceExists($type, $year);

        return $this->db->transaction(function () use ($type, $year) {
            $sequence = DocumentNumberSequence::query()
                ->where('type', $type->value)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            return sprintf('%s-%d-%04d', $type->numberPrefix(), $year, $sequence->last_number);
        });
    }

    private function ensureSequenceExists(DocumentType $type, int $year): void
    {
        // firstOrCreate() already survives "another request created it
        // between our SELECT and our INSERT" itself — Eloquent's
        // createOrFirst() catches the unique constraint violation and
        // re-queries for the row a concurrent request just created.
        DocumentNumberSequence::query()->firstOrCreate(
            ['type' => $type->value, 'year' => $year],
            ['last_number' => 0]
        );
    }
}
