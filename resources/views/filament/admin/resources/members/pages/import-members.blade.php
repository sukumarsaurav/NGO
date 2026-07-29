<x-filament-panels::page>
    <form wire:submit.prevent="parseAndPreview">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Preview (dry run)
            </x-filament::button>
        </div>
    </form>

    @if ($summary)
        <div class="mt-8 space-y-4">
            <div class="rounded-lg border border-line-divider bg-surface p-4">
                <p class="text-content">
                    <span class="font-semibold">{{ $summary['total'] }}</span> rows —
                    <span class="font-semibold text-success">{{ $summary['valid'] }} valid</span>,
                    <span class="font-semibold text-danger">{{ $summary['errorRows'] }} with errors</span>.
                </p>
            </div>

            @if (count($summary['errors']) > 0)
                <div class="overflow-x-auto rounded-lg border border-line-divider">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface-muted">
                            <tr>
                                <th class="px-4 py-2">Row</th>
                                <th class="px-4 py-2">Field</th>
                                <th class="px-4 py-2">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['errors'] as $error)
                                <tr class="border-t border-line-divider">
                                    <td class="px-4 py-2">{{ $error['row'] }}</td>
                                    <td class="px-4 py-2">{{ $error['field'] }}</td>
                                    <td class="px-4 py-2">{{ $error['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (! $imported && $summary['valid'] > 0)
                <x-filament::button wire:click="confirmImport" color="success">
                    Import {{ $summary['valid'] }} valid rows
                </x-filament::button>
            @endif

            @if ($imported)
                <p class="font-semibold text-success">Import complete.</p>
            @endif
        </div>
    @endif
</x-filament-panels::page>
