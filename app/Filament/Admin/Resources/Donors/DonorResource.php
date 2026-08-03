<?php

namespace App\Filament\Admin\Resources\Donors;

use App\Filament\Admin\Resources\Donors\Pages\ListDonors;
use App\Filament\Admin\Resources\Donors\Pages\ViewDonor;
use App\Filament\Admin\Resources\Donors\Schemas\DonorInfolist;
use App\Filament\Admin\Resources\Donors\Tables\DonorsTable;
use App\Models\Donor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DonorResource extends Resource
{
    protected static ?string $model = Donor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Donations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DonorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DonorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDonors::route('/'),
            'view' => ViewDonor::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
