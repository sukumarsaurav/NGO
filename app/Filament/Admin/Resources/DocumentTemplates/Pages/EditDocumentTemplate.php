<?php

namespace App\Filament\Admin\Resources\DocumentTemplates\Pages;

use App\Filament\Admin\Resources\DocumentTemplates\DocumentTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentTemplate extends EditRecord
{
    protected static string $resource = DocumentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
