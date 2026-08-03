<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentsController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->user()->member;

        return view('portal.documents.index', [
            'documents' => $member
                ? $member->issuedDocuments()->whereIn('status', ['issued', 'revoked'])->latest('issued_on')->get()
                : collect(),
        ]);
    }

    public function download(Request $request, IssuedDocument $document): StreamedResponse|RedirectResponse
    {
        $member = $request->user()->member;

        abort_unless($member && $document->member_id === $member->id, Response::HTTP_FORBIDDEN);
        abort_unless($document->file_path !== null, Response::HTTP_NOT_FOUND);

        $document->increment('download_count');

        return Storage::disk('local')->download($document->file_path, "{$document->document_number}.pdf");
    }
}
