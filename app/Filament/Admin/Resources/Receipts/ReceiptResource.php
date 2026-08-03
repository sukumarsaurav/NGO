<?php

namespace App\Filament\Admin\Resources\Receipts;

use App\Filament\Admin\Resources\Receipts\Pages\ListReceipts;
use App\Filament\Admin\Resources\Receipts\Pages\ViewReceipt;
use App\Filament\Admin\Resources\Receipts\Schemas\ReceiptInfolist;
use App\Filament\Admin\Resources\Receipts\Tables\ReceiptsTable;
use App\Models\Receipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Donations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceiptsTable::configure($table);
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
            'index' => ListReceipts::route('/'),
            'view' => ViewReceipt::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
