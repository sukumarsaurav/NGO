<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Notices\Pages;

use App\Enums\NoticeAudience;
use App\Filament\Manager\Resources\Notices\NoticeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditNotice extends EditRecord
{
    protected static string $resource = NoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['department_ids'] = $data['audience_filter']['department_ids'] ?? [];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $departmentIds = array_map(intval(...), $data['department_ids'] ?? []);
        $managed = Auth::user()->managedDepartmentIds();

        if ($departmentIds === [] || array_diff($departmentIds, $managed) !== []) {
            throw ValidationException::withMessages([
                'department_ids' => 'You can only publish to departments you manage.',
            ]);
        }

        $data['audience'] = NoticeAudience::Department->value;
        $data['audience_filter'] = ['department_ids' => $departmentIds];
        unset($data['department_ids']);

        return $data;
    }
}
