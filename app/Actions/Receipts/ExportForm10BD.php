<?php

declare(strict_types=1);

namespace App\Actions\Receipts;

use App\Services\Export\Form10BDExporter;

final class ExportForm10BD
{
    public function __construct(
        private readonly Form10BDExporter $exporter,
    ) {}

    public function handle(string $financialYear): string
    {
        $rows = $this->exporter->rows($financialYear);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, Form10BDExporter::HEADERS);

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }
}
