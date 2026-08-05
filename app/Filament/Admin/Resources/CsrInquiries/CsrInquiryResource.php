<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CsrInquiries;

use App\Filament\Admin\Resources\CsrInquiries\Pages\ListCsrInquiries;
use App\Filament\Admin\Resources\CsrInquiries\Pages\ViewCsrInquiry;
use App\Filament\Admin\Resources\CsrInquiries\Schemas\CsrInquiryInfolist;
use App\Filament\Admin\Resources\CsrInquiries\Tables\CsrInquiriesTable;
use App\Models\CsrInquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Submissions only come from the public CSR Partnership form — reviewed
 * here, never created here. Same read-only-creation shape as
 * ContactMessageResource.
 */
class CsrInquiryResource extends Resource
{
    protected static ?string $model = CsrInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'CSR Inquiries';

    public static function infolist(Schema $schema): Schema
    {
        return CsrInquiryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CsrInquiriesTable::configure($table);
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
            'index' => ListCsrInquiries::route('/'),
            'view' => ViewCsrInquiry::route('/{record}'),
        ];
    }
}
