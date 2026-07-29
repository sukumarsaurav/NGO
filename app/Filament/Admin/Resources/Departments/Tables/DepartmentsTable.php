<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Departments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Flat table with a parent-name column, not a drag-and-drop tree widget —
 * Filament ships no built-in tree table, and building one is disproportionate
 * to what Sprint 3 needs. Hierarchy is visible via the "Parent" column and
 * enforceable via the form (see DepartmentForm's cycle-prevention).
 *
 * Deleting a department with members fails at the database level
 * (members.department_id is ON DELETE RESTRICT) — see the "deleting a
 * department that has members" edge case in docs/modules/M03-members.md.
 */
class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('parent.name')->label('Parent')->placeholder('— top level —'),
                TextColumn::make('manager.name')->label('Manager')->placeholder('— unassigned —'),
                TextColumn::make('members_count')->label('Members')->counts('members'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
