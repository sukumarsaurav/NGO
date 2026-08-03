<?php

namespace App\Filament\Admin\Resources\PressMentions\Pages;

use App\Filament\Admin\Resources\PressMentions\PressMentionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPressMentions extends ListRecords
{
    protected static string $resource = PressMentionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
