@php
    $pageSize = strtoupper($pageSize ?? 'A4');
    $frameWidth = $pageSize === 'CR80' ? '340px' : '480px';
    $frameHeight = $pageSize === 'CR80' ? '215px' : '640px';

    try {
        $body = \Illuminate\Support\Facades\Blade::render($bodyHtml ?? '', [
            'member' => (object) [
                'name' => 'Ananya Desai',
                'member_code' => 'VGWGF-2026-00001',
                'designation' => 'Volunteer Coordinator',
                'department' => 'Fundraising',
            ],
            'document' => (object) [
                'document_number' => 'AL-2026-0001',
                'title' => 'Sample document',
                'issued_on' => now(),
            ],
            'qr' => null,
        ]);
        $error = null;
    } catch (\Throwable $e) {
        $body = '';
        $error = $e->getMessage();
    }

    $srcdoc = '<html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;margin:0;}'
        .($css ?? '')
        .'</style></head><body>'.$body.'</body></html>';
@endphp

<div class="rounded-lg border border-line-divider bg-surface-muted p-4">
    <p class="mb-2 text-xs font-medium text-content-muted">Live preview (sample data)</p>

    @if ($error)
        <p class="text-sm text-danger">Template error: {{ $error }}</p>
    @else
        <iframe
            srcdoc="{{ $srcdoc }}"
            style="width: {{ $frameWidth }}; height: {{ $frameHeight }}; border: 1px solid #e5e7eb; background: white;"
        ></iframe>
    @endif
</div>
