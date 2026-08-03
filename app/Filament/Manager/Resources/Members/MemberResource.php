<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Members;

use App\Filament\Manager\Resources\Members\Pages\CreateMember;
use App\Filament\Manager\Resources\Members\Pages\EditMember;
use App\Filament\Manager\Resources\Members\Pages\ListMembers;
use App\Filament\Manager\Resources\Members\Schemas\MemberForm;
use App\Filament\Manager\Resources\Members\Tables\MembersTable;
use App\Models\Member;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * `members.department_id` exists directly on the table, so the scope is a
 * plain `whereIn` — see docs/modules/M11-manager-panel.md's Layer 1 example.
 * `MemberPolicy` (shared with the Admin panel) is Layer 2.
 */
class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'member_code';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('department_id', Auth::user()->managedDepartmentIds());
    }

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}
