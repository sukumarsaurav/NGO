<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PressMentions;

use App\Filament\Admin\Resources\PressMentions\Pages\CreatePressMention;
use App\Filament\Admin\Resources\PressMentions\Pages\EditPressMention;
use App\Filament\Admin\Resources\PressMentions\Pages\ListPressMentions;
use App\Filament\Admin\Resources\PressMentions\Schemas\PressMentionForm;
use App\Filament\Admin\Resources\PressMentions\Tables\PressMentionsTable;
use App\Models\PressMention;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PressMentionResource extends Resource
{
    protected static ?string $model = PressMention::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Press Mentions';

    public static function form(Schema $schema): Schema
    {
        return PressMentionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PressMentionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPressMentions::route('/'),
            'create' => CreatePressMention::route('/create'),
            'edit' => EditPressMention::route('/{record}/edit'),
        ];
    }
}
