<x-layout.portal title="Notices">
    <h1 class="mb-6 text-2xl font-bold text-content">Notices</h1>

    @if ($recipients->isEmpty())
        <x-empty-state title="No notices">
            Announcements sent to you will appear here.
        </x-empty-state>
    @else
        <div class="divide-y divide-line-divider overflow-hidden rounded-lg border border-line-divider bg-surface shadow-sm">
            @foreach ($recipients as $recipient)
                <a href="{{ route('portal.notices.show', $recipient->notice) }}" class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-background">
                    <div>
                        <div class="flex items-center gap-2">
                            @if (! $recipient->read_at)
                                <span class="h-2 w-2 rounded-full bg-action"></span>
                            @endif
                            <span class="font-semibold text-content">{{ $recipient->notice->title }}</span>
                            @if ($recipient->notice->priority->value !== 'normal')
                                <x-badge :variant="$recipient->notice->priority->value === 'urgent' ? 'danger' : 'warning'">
                                    {{ $recipient->notice->priority->label() }}
                                </x-badge>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-content-muted">{{ $recipient->notice->published_at?->format('d M Y') }}</p>
                    </div>
                    @if ($recipient->notice->attachment_path)
                        <span class="text-xs text-content-muted">Attachment</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</x-layout.portal>
