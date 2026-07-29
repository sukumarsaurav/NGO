<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Departments\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * See docs/modules/M03-members.md — "tree view for nested departments,
 * assign a manager user."
 */
class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true),
                Select::make('parent_id')
                    ->label('Parent department')
                    ->options(function (Get $get, ?Department $record) {
                        // A department can't be its own parent or a descendant
                        // of itself — see the "circular department nesting"
                        // edge case in docs/modules/M03-members.md.
                        $excluded = $record ? $record->selfAndDescendantIds() : [];

                        return Department::query()
                            ->when($excluded, fn ($q) => $q->whereNotIn('id', $excluded))
                            ->pluck('name', 'id');
                    })
                    ->searchable(),
                Select::make('manager_user_id')
                    ->label('Manager')
                    ->relationship('manager', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Drives /manager panel scoping — see docs/modules/M11-manager-panel.md.'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
