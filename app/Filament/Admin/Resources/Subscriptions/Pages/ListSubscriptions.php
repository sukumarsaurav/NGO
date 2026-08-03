<?php

namespace App\Filament\Admin\Resources\Subscriptions\Pages;

use App\Filament\Admin\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Admin\Widgets\SubscriptionStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            SubscriptionStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
