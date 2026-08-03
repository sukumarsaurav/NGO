<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Campaigns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CampaignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('title'),
                TextEntry::make('category.name')->label('Cause'),
                TextEntry::make('status')->badge(),
                TextEntry::make('beneficiary_name')->placeholder('—'),
                TextEntry::make('raised_amount')->label('Raised')->money('inr', divideBy: 100),
                TextEntry::make('goal_amount')->label('Goal')->money('inr', divideBy: 100),
                TextEntry::make('donor_count')->label('Donors'),
                TextEntry::make('created_at')->dateTime(),
            ]),
            TextEntry::make('story')->html()->columnSpanFull(),
        ]);
    }
}
