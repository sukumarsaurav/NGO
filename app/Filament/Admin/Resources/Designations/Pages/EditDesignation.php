<?php

namespace App\Filament\Admin\Resources\Designations\Pages;

use App\Filament\Admin\Resources\Designations\DesignationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDesignation extends EditRecord
{
    protected static string $resource = DesignationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
