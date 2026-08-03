<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Cms\SubmitContactMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `/contact` — honeypot + 3/hour/IP rate limit (route middleware). See
 * docs/modules/M10-public-site-cms.md's "Contact messages".
 */
class ContactController extends Controller
{
    public function show(): View
    {
        return view('public.contact.show');
    }

    public function store(Request $request, SubmitContactMessage $submit): RedirectResponse
    {
        // Honeypot — a real visitor never fills this hidden field. Silently
        // pretend success so the bot doesn't learn to avoid it.
        if ($request->filled('website')) {
            return back()->with('status', 'Thanks — we\'ve received your message.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $submit->handle([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', "Thanks — we've received your message and will get back to you soon.");
    }
}
