<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CampaignCategories;

use App\Filament\Admin\Resources\CampaignCategories\Pages\CreateCampaignCategory;
use App\Filament\Admin\Resources\CampaignCategories\Pages\EditCampaignCategory;
use App\Filament\Admin\Resources\CampaignCategories\Pages\ListCampaignCategories;
use App\Filament\Admin\Resources\CampaignCategories\Schemas\CampaignCategoryForm;
use App\Filament\Admin\Resources\CampaignCategories\Tables\CampaignCategoriesTable;
use App\Models\CampaignCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CampaignCategoryResource extends Resource
{
    protected static ?string $model = CampaignCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Campaigns';

    protected static ?string $navigationLabel = 'Causes';

    public static function form(Schema $schema): Schema
    {
        return CampaignCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignCategories::route('/'),
            'create' => CreateCampaignCategory::route('/create'),
            'edit' => EditCampaignCategory::route('/{record}/edit'),
        ];
    }
}
