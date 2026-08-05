<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\InternshipApplications;

use App\Filament\Admin\Resources\InternshipApplications\Pages\ListInternshipApplications;
use App\Filament\Admin\Resources\InternshipApplications\Pages\ViewInternshipApplication;
use App\Filament\Admin\Resources\InternshipApplications\Schemas\InternshipApplicationInfolist;
use App\Filament\Admin\Resources\InternshipApplications\Tables\InternshipApplicationsTable;
use App\Models\InternshipApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Submissions only come from the public Internship form — reviewed here,
 * never created here. Same read-only-creation shape as ContactMessageResource.
 */
class InternshipApplicationResource extends Resource
{
    protected static ?string $model = InternshipApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Internship Applications';

    public static function infolist(Schema $schema): Schema
    {
        return InternshipApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternshipApplicationsTable::configure($table);
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
            'index' => ListInternshipApplications::route('/'),
            'view' => ViewInternshipApplication::route('/{record}'),
        ];
    }
}
