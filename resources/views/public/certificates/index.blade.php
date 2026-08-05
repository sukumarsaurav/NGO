<x-layout.public title="Certificates" description="Our registration and compliance certificates.">
    <div class="w-full max-w-2xl">
        <h1 class="mb-2 text-center text-2xl font-bold text-content sm:text-3xl">Certificates &amp; Registrations</h1>
        <p class="mx-auto mb-8 max-w-xl text-center text-content-muted">
            Our registration and tax-exemption certificates, available to view or download.
        </p>

        @if ($certificates->isEmpty())
            <x-empty-state title="No certificates published yet">
                Check back soon.
            </x-empty-state>
        @else
            <div class="space-y-4">
                @foreach ($certificates as $certificate)
                    <div class="flex flex-col gap-3 rounded-lg border border-line-divider bg-surface p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-content">{{ $certificate->title }}</p>
                            @if ($certificate->issuing_authority)
                                <p class="text-sm text-content-muted">{{ $certificate->issuing_authority }}</p>
                            @endif
                            @if ($certificate->description)
                                <p class="mt-1 text-sm text-content-muted">{{ $certificate->description }}</p>
                            @endif
                            @if ($certificate->valid_from || $certificate->valid_until)
                                <p class="mt-1 text-xs text-content-muted">
                                    Valid
                                    @if ($certificate->valid_from) from {{ $certificate->valid_from->format('d M Y') }} @endif
                                    @if ($certificate->valid_until) to {{ $certificate->valid_until->format('d M Y') }} @endif
                                </p>
                            @endif
                        </div>
                        <a
                            href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($certificate->file_path) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex shrink-0 min-h-touch items-center justify-center rounded-md border border-line px-4 py-2 text-sm font-semibold text-content transition-colors duration-fast hover:bg-surface-muted"
                        >
                            View / Download
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout.public>
