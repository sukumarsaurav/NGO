<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\Pages;

use App\Actions\Campaigns\CreateCampaign as CreateCampaignAction;
use App\Filament\Admin\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function handleRecordCreation(array $data): Campaign
    {
        $data['created_by_user_id'] = auth()->id();

        return app(CreateCampaignAction::class)->handle($data);
    }
}
