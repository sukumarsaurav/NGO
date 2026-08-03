<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Member/donor notice inbox. Read tracking is honest portal-open data, not
 * an email open pixel — see docs/modules/M09-notices-communication.md.
 */
class NoticesController extends Controller
{
    public function index(Request $request): View
    {
        $recipients = NoticeRecipient::query()
            ->with('notice')
            ->where('user_id', $request->user()->id)
            ->whereHas('notice', fn ($query) => $query->whereNotNull('published_at'))
            ->latest('created_at')
            ->get();

        return view('portal.notices.index', [
            'recipients' => $recipients,
            'unreadCount' => $recipients->whereNull('read_at')->count(),
        ]);
    }

    public function show(Request $request, Notice $notice): View
    {
        $recipient = NoticeRecipient::query()
            ->where('notice_id', $notice->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless($notice->published_at !== null, Response::HTTP_NOT_FOUND);

        if (! $recipient->read_at) {
            $recipient->update(['read_at' => now()]);
            $notice->increment('read_count');
        }

        return view('portal.notices.show', [
            'notice' => $notice,
            'attachmentUrl' => $notice->attachment_path ? Storage::disk('public')->url($notice->attachment_path) : null,
        ]);
    }
}
