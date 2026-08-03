<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FundraiserRequests;

use App\Filament\Admin\Resources\FundraiserRequests\Pages\ListFundraiserRequests;
use App\Filament\Admin\Resources\FundraiserRequests\Pages\ViewFundraiserRequest;
use App\Filament\Admin\Resources\FundraiserRequests\Schemas\FundraiserRequestInfolist;
use App\Filament\Admin\Resources\FundraiserRequests\Tables\FundraiserRequestsTable;
use App\Models\FundraiserRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FundraiserRequestResource extends Resource
{
    protected static ?string $model = FundraiserRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Campaigns';

    protected static ?string $navigationLabel = 'Fundraiser Requests';

    public static function infolist(Schema $schema): Schema
    {
        return FundraiserRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FundraiserRequestsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFundraiserRequests::route('/'),
            'view' => ViewFundraiserRequest::route('/{record}'),
        ];
    }
}
