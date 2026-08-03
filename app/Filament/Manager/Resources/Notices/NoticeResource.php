<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Notices;

use App\Enums\NoticeAudience;
use App\Filament\Manager\Resources\Notices\Pages\CreateNotice;
use App\Filament\Manager\Resources\Notices\Pages\EditNotice;
use App\Filament\Manager\Resources\Notices\Pages\ListNotices;
use App\Filament\Manager\Resources\Notices\Schemas\NoticeForm;
use App\Filament\Manager\Resources\Notices\Tables\NoticesTable;
use App\Models\Notice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * `notices` has no `department_id` column — it has `audience` +
 * `audience_filter` JSON. A manager sees notices they created, plus
 * department-targeted notices aimed at a department they manage. Verbatim
 * from docs/modules/M11-manager-panel.md's NoticeResource scope example.
 */
class NoticeResource extends Resource
{
    protected static ?string $model = Notice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function getEloquentQuery(): Builder
    {
        $ids = Auth::user()->managedDepartmentIds();

        return parent::getEloquentQuery()
            ->where(fn (Builder $q) => $q
                ->where('created_by_user_id', Auth::id())
                ->orWhere(fn (Builder $q2) => $q2
                    ->where('audience', NoticeAudience::Department->value)
                    ->whereJsonOverlaps('audience_filter->department_ids', $ids)));
    }

    public static function form(Schema $schema): Schema
    {
        return NoticeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NoticesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotices::route('/'),
            'create' => CreateNotice::route('/create'),
            'edit' => EditNotice::route('/{record}/edit'),
        ];
    }
}
