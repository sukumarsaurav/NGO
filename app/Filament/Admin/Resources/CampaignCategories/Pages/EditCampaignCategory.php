<?php

namespace App\Filament\Admin\Resources\CampaignCategories\Pages;

use App\Filament\Admin\Resources\CampaignCategories\CampaignCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaignCategory extends EditRecord
{
    protected static string $resource = CampaignCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
