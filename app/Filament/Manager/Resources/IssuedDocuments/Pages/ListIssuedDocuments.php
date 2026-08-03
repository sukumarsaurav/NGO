<?php

namespace App\Filament\Manager\Resources\IssuedDocuments\Pages;

use App\Filament\Manager\Resources\IssuedDocuments\IssuedDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListIssuedDocuments extends ListRecords
{
    protected static string $resource = IssuedDocumentResource::class;
}
