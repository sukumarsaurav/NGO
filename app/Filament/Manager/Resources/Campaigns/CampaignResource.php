<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Campaigns;

use App\Filament\Manager\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Manager\Resources\Campaigns\Pages\ViewCampaign;
use App\Filament\Manager\Resources\Campaigns\Schemas\CampaignInfolist;
use App\Filament\Manager\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CampaignInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'view' => ViewCampaign::route('/{record}'),
        ];
    }
}
