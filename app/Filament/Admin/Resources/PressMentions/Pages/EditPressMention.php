<?php

namespace App\Filament\Admin\Resources\PressMentions\Pages;

use App\Filament\Admin\Resources\PressMentions\PressMentionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPressMention extends EditRecord
{
    protected static string $resource = PressMentionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
