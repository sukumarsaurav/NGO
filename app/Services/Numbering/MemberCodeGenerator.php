<?php

declare(strict_types=1);

namespace App\Services\Numbering;

use App\Models\MemberCodeSequence;
use App\Services\Settings\SettingsRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Produces `{PREFIX}-{YYYY}-{NNNNN}`, e.g. `VGWGF-2026-00123`. Sequence is
 * per calendar year, row-locked. See docs/modules/M03-members.md.
 *
 * Same two-step pattern as ReceiptNumberGenerator (M07) and for the same
 * reason: `lockForUpdate()` on a row that doesn't exist yet locks nothing, so
 * the sequence row must be created OUTSIDE the transaction, before the lock
 * is taken. Two members created together on 1 January is the member-code
 * equivalent of the 1 April receipt race — lower stakes, same bug shape.
 */
final class MemberCodeGenerator
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly SettingsRepository $settings,
    ) {}

    public function next(): string
    {
        $year = (int) now()->year;

        $this->ensureSequenceExists($year);

        return $this->db->transaction(function () use ($year) {
            $sequence = MemberCodeSequence::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            $prefix = $this->settings->get('org.member_code_prefix', 'ORG');

            return sprintf('%s-%d-%05d', $prefix, $year, $sequence->last_number);
        });
    }

    private function ensureSequenceExists(int $year): void
    {
        try {
            MemberCodeSequence::query()->firstOrCreate(
                ['year' => $year],
                ['last_number' => 0]
            );
        } catch (UniqueConstraintViolationException) {
            // Another request created it between our SELECT and our INSERT —
            // that is the outcome we wanted anyway.
        }
    }
}
