<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Cms\SubmitInternshipApplication;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `/internship` — honeypot + 3/hour/IP rate limit (route middleware), same
 * shape as ContactController. Resume upload goes to the `local` (private)
 * disk, same convention as FundraiserRequestController's `documents[]`.
 */
class InternshipController extends Controller
{
    public function show(): View
    {
        return view('public.internship.show');
    }

    public function store(Request $request, SubmitInternshipApplication $submit): RedirectResponse
    {
        // Honeypot — a real visitor never fills this hidden field. Silently
        // pretend success so the bot doesn't learn to avoid it.
        if ($request->filled('website')) {
            return back()->with('status', "Thanks — we've received your application.");
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:20'],
            'track' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:5000'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $resumePath = $request->hasFile('resume')
            ? $request->file('resume')->store('internship-applications', 'local')
            : null;

        $submit->handle([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'track' => $data['track'] ?? null,
            'message' => $data['message'] ?? null,
            'resume_path' => $resumePath,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', "Thanks — we've received your application and will be in touch soon.");
    }
}
