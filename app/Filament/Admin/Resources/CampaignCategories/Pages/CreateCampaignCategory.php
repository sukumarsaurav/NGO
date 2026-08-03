<?php

namespace App\Filament\Admin\Resources\CampaignCategories\Pages;

use App\Filament\Admin\Resources\CampaignCategories\CampaignCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaignCategory extends CreateRecord
{
    protected static string $resource = CampaignCategoryResource::class;
}
