<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\IssuedDocuments;

use App\Filament\Admin\Resources\IssuedDocuments\Schemas\IssuedDocumentInfolist;
use App\Filament\Admin\Resources\IssuedDocuments\Tables\IssuedDocumentsTable;
use App\Filament\Manager\Resources\IssuedDocuments\Pages\ListIssuedDocuments;
use App\Filament\Manager\Resources\IssuedDocuments\Pages\ViewIssuedDocument;
use App\Models\IssuedDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * `issued_documents` has `member_id`, not `department_id` — the scope joins
 * through `member`. Verbatim from docs/modules/M11-manager-panel.md's
 * IssuedDocumentResource scope example. Table/infolist are reused directly
 * from the Admin panel — already read-only-shaped (issuing happens from
 * `MemberResource`), and the `revoke` action's visibility now checks
 * `IssuedDocumentPolicy::revoke()` (Layer 2: "only ones they issued").
 */
class IssuedDocumentResource extends Resource
{
    protected static ?string $model = IssuedDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('member', fn (Builder $q) => $q
                ->whereIn('department_id', Auth::user()->managedDepartmentIds()));
    }

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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIssuedDocuments::route('/'),
            'view' => ViewIssuedDocument::route('/{record}'),
        ];
    }
}
