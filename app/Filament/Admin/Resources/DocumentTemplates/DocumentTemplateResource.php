<?php

namespace App\Filament\Admin\Resources\DocumentTemplates;

use App\Filament\Admin\Resources\DocumentTemplates\Pages\CreateDocumentTemplate;
use App\Filament\Admin\Resources\DocumentTemplates\Pages\EditDocumentTemplate;
use App\Filament\Admin\Resources\DocumentTemplates\Pages\ListDocumentTemplates;
use App\Filament\Admin\Resources\DocumentTemplates\Schemas\DocumentTemplateForm;
use App\Filament\Admin\Resources\DocumentTemplates\Tables\DocumentTemplatesTable;
use App\Models\DocumentTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocumentTemplateResource extends Resource
{
    protected static ?string $model = DocumentTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    public static function form(Schema $schema): Schema
    {
        return DocumentTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentTemplatesTable::configure($table);
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
            'index' => ListDocumentTemplates::route('/'),
            'create' => CreateDocumentTemplate::route('/create'),
            'edit' => EditDocumentTemplate::route('/{record}/edit'),
        ];
    }
}
