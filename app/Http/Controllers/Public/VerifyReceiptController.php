<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Illuminate\View\View;

/**
 * Public, unauthenticated — what the QR printed on a receipt PDF opens to.
 * Mirrors VerifyDocumentController's shape (M04) for the same reason: a
 * donor or an auditor should be able to confirm a receipt is genuine without
 * logging in.
 */
class VerifyReceiptController extends Controller
{
    public function show(string $uuid): View
    {
        $receipt = Receipt::query()->where('uuid', $uuid)->first();

        if (! $receipt) {
            return view('public.verify-receipt', ['state' => 'not_found']);
        }

        return view('public.verify-receipt', [
            'state' => $receipt->is_cancelled ? 'cancelled' : 'valid',
            'receipt' => $receipt,
        ]);
    }
}
