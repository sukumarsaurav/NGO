<?php

namespace App\Filament\Admin\Resources\ImpactStats\Pages;

use App\Filament\Admin\Resources\ImpactStats\ImpactStatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImpactStat extends EditRecord
{
    protected static string $resource = ImpactStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
