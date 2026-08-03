<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\IssuedDocument;
use InvalidArgumentException;

/**
 * Revokes an issued document — the verify page will show "REVOKED" with the
 * reason from the moment this runs. Revoking is terminal; a revoked document
 * is never reinstated. To replace it, issue a fresh one and call
 * `supersede()` to link the old row forward.
 */
final class RevokeDocument
{
    public function handle(IssuedDocument $document, string $reason): IssuedDocument
    {
        if ($document->status !== DocumentStatus::Issued) {
            throw new InvalidArgumentException(
                "Only an issued document can be revoked; this one is '{$document->status->value}'."
            );
        }

        $document->update([
            'status' => DocumentStatus::Revoked->value,
            'revoked_at' => now(),
            'revoked_reason' => $reason,
        ]);

        return $document->fresh();
    }

    /**
     * Marks $old as superseded by $new — used after reissuing a document
     * (e.g. a corrected appointment letter). $old keeps its own status
     * (issued or revoked); only `superseded_by_id` changes, plus its status
     * flips to `superseded` so it stops showing as the active document.
     */
    public function supersede(IssuedDocument $old, IssuedDocument $new): IssuedDocument
    {
        $old->update([
            'status' => DocumentStatus::Superseded->value,
            'superseded_by_id' => $new->id,
        ]);

        return $old->fresh();
    }
}
