<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Cms\SubmitCsrInquiry;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `/csr-partnership` — honeypot + 3/hour/IP rate limit (route middleware),
 * same shape as ContactController.
 */
class CsrPartnershipController extends Controller
{
    public function show(): View
    {
        return view('public.csr-partnership.show');
    }

    public function store(Request $request, SubmitCsrInquiry $submit): RedirectResponse
    {
        // Honeypot — a real visitor never fills this hidden field. Silently
        // pretend success so the bot doesn't learn to avoid it.
        if ($request->filled('website')) {
            return back()->with('status', "Thanks — we've received your inquiry.");
        }

        $data = $request->validate([
            'organisation_name' => ['required', 'string', 'max:190'],
            'contact_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $submit->handle([
            'organisation_name' => $data['organisation_name'],
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'message' => $data['message'],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', "Thanks — we've received your inquiry and will be in touch soon.");
    }
}
