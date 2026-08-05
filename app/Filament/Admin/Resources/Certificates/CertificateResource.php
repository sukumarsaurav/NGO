<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Certificates;

use App\Filament\Admin\Resources\Certificates\Pages\CreateCertificate;
use App\Filament\Admin\Resources\Certificates\Pages\EditCertificate;
use App\Filament\Admin\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Admin\Resources\Certificates\Schemas\CertificateForm;
use App\Filament\Admin\Resources\Certificates\Tables\CertificatesTable;
use App\Models\Certificate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    public static function form(Schema $schema): Schema
    {
        return CertificateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CertificatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'create' => CreateCertificate::route('/create'),
            'edit' => EditCertificate::route('/{record}/edit'),
        ];
    }
}
