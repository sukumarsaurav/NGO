<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FundraiserRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class FundraiserRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('phone'),
                TextEntry::make('organisation_name')->label('Organisation')->placeholder('—'),
                TextEntry::make('causeCategory.name')->label('Cause category')->placeholder('Not set'),
                TextEntry::make('goal_amount')->label('Goal')->money('inr', divideBy: 100),
                TextEntry::make('status')->badge(),
                TextEntry::make('campaign.title')->label('Campaign created')->placeholder('—'),
            ]),
            TextEntry::make('title')->label('Fundraiser title')->columnSpanFull(),
            TextEntry::make('description')->columnSpanFull(),
            TextEntry::make('review_notes')->label('Review notes')->placeholder('—')->columnSpanFull(),
            TextEntry::make('reviewedBy.name')->label('Reviewed by')->placeholder('—'),
            TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
        ]);
    }
}
