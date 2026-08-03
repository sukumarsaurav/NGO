<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use Illuminate\View\View;

/**
 * Public, unauthenticated — this is what a phone camera lands on after
 * scanning the QR printed on an ID card, letter, or certificate. Three
 * states: valid, revoked, not-found. See docs/03-ROADMAP.md's Sprint 4
 * acceptance criteria.
 */
class VerifyDocumentController extends Controller
{
    public function show(string $uuid): View
    {
        $document = IssuedDocument::query()
            ->with('member.user')
            ->where('uuid', $uuid)
            ->first();

        if (! $document) {
            return view('public.verify', ['state' => 'not_found']);
        }

        $document->increment('verified_count');

        $state = match ($document->status) {
            DocumentStatus::Issued => 'valid',
            DocumentStatus::Revoked => 'revoked',
            DocumentStatus::Superseded => 'superseded',
            DocumentStatus::Queued => 'not_found',
        };

        return view('public.verify', [
            'state' => $state,
            'document' => $document,
        ]);
    }
}
