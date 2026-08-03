<x-layout.portal :title="$notice->title">
    <a href="{{ route('portal.notices.index') }}" class="mb-4 inline-block text-sm text-link hover:text-link-hover">&larr; Back to notices</a>

    <div class="rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-8">
        <div class="mb-4 flex items-center gap-2">
            <h1 class="text-2xl font-bold text-content">{{ $notice->title }}</h1>
            @if ($notice->priority->value !== 'normal')
                <x-badge :variant="$notice->priority->value === 'urgent' ? 'danger' : 'warning'">
                    {{ $notice->priority->label() }}
                </x-badge>
            @endif
        </div>
        <p class="mb-6 text-sm text-content-muted">{{ $notice->published_at?->format('d M Y, h:i A') }}</p>

        <div class="prose prose-sm max-w-none text-content">
            {{-- Sanitized despite being admin-authored — Filament's own
                 RichEditor docs warn its state can be tampered with via a
                 direct request to the Livewire update endpoint, bypassing
                 the client-side editor entirely. See
                 vendor/filament/forms/src/Components/RichEditor.php. --}}
            {!! str($notice->body)->sanitizeHtml() !!}
        </div>

        @if ($attachmentUrl)
            <a href="{{ $attachmentUrl }}" class="mt-6 inline-block font-semibold text-link hover:text-link-hover">
                Download attachment
            </a>
        @endif
    </div>
</x-layout.portal>
