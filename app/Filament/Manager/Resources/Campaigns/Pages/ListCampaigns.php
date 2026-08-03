<?php

namespace App\Filament\Manager\Resources\Campaigns\Pages;

use App\Filament\Manager\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;
}
