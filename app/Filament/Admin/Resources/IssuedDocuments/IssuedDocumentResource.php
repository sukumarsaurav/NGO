<?php

namespace App\Filament\Admin\Resources\IssuedDocuments;

use App\Filament\Admin\Resources\IssuedDocuments\Pages\ListIssuedDocuments;
use App\Filament\Admin\Resources\IssuedDocuments\Pages\ViewIssuedDocument;
use App\Filament\Admin\Resources\IssuedDocuments\Schemas\IssuedDocumentInfolist;
use App\Filament\Admin\Resources\IssuedDocuments\Tables\IssuedDocumentsTable;
use App\Models\IssuedDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read-only by design — documents are created via IssueIdCard /
 * IssueAppointmentLetter / IssueCertificate (triggered from MemberResource),
 * never hand-edited here. This resource is for browsing, viewing, and
 * revoking what's already been issued.
 */
class IssuedDocumentResource extends Resource
{
    protected static ?string $model = IssuedDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IssuedDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IssuedDocumentsTable::configure($table);
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
            'index' => ListIssuedDocuments::route('/'),
            'view' => ViewIssuedDocument::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
