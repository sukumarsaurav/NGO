<x-layout.portal title="Documents">
    <h1 class="mb-6 text-2xl font-bold text-content">My documents</h1>

    @if ($documents->isEmpty())
        <x-empty-state title="No documents yet">
            Anything issued to you — certificates, letters, receipts — will appear here.
        </x-empty-state>
    @else
        <div class="overflow-hidden rounded-lg border border-line-divider bg-surface shadow-sm">
            <table class="min-w-full divide-y divide-line-divider">
                <thead>
                    <tr class="text-left text-sm text-content-muted">
                        <th class="px-4 py-3 font-medium">Document</th>
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Issued on</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line-divider">
                    @foreach ($documents as $document)
                        <tr>
                            <td class="px-4 py-3 text-content">{{ $document->title }}</td>
                            <td class="px-4 py-3 text-content-muted">{{ $document->document_number }}</td>
                            <td class="px-4 py-3 text-content-muted">{{ $document->issued_on->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$document->status->value === 'revoked' ? 'danger' : 'success'">
                                    {{ $document->status->label() }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($document->file_path)
                                    <a href="{{ route('portal.documents.download', $document) }}" class="font-semibold text-link hover:text-link-hover">
                                        Download
                                    </a>
                                @else
                                    <span class="text-content-muted">Generating…</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layout.portal>
