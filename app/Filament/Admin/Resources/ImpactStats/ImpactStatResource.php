<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ImpactStats;

use App\Filament\Admin\Resources\ImpactStats\Pages\CreateImpactStat;
use App\Filament\Admin\Resources\ImpactStats\Pages\EditImpactStat;
use App\Filament\Admin\Resources\ImpactStats\Pages\ListImpactStats;
use App\Filament\Admin\Resources\ImpactStats\Schemas\ImpactStatForm;
use App\Filament\Admin\Resources\ImpactStats\Tables\ImpactStatsTable;
use App\Models\ImpactStat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ImpactStatResource extends Resource
{
    protected static ?string $model = ImpactStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Impact Stats';

    public static function form(Schema $schema): Schema
    {
        return ImpactStatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImpactStatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImpactStats::route('/'),
            'create' => CreateImpactStat::route('/create'),
            'edit' => EditImpactStat::route('/{record}/edit'),
        ];
    }
}
