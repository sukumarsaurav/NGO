<?php

namespace App\Filament\Admin\Resources\IssuedDocuments\Pages;

use App\Filament\Admin\Resources\IssuedDocuments\IssuedDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListIssuedDocuments extends ListRecords
{
    protected static string $resource = IssuedDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
