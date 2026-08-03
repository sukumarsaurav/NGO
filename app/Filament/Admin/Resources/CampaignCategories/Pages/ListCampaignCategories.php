<?php

namespace App\Filament\Admin\Resources\CampaignCategories\Pages;

use App\Filament\Admin\Resources\CampaignCategories\CampaignCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaignCategories extends ListRecords
{
    protected static string $resource = CampaignCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
