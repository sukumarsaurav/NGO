<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notices\Pages;

use App\Filament\Admin\Resources\Notices\NoticeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotice extends EditRecord
{
    protected static string $resource = NoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $filter = $data['audience_filter'] ?? [];
        $data['department_ids'] = $filter['department_ids'] ?? [];
        $data['designation_ids'] = $filter['designation_ids'] ?? [];
        $data['user_ids'] = $filter['user_ids'] ?? [];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['audience_filter'] = [
            'department_ids' => $data['department_ids'] ?? [],
            'designation_ids' => $data['designation_ids'] ?? [],
            'user_ids' => $data['user_ids'] ?? [],
        ];
        unset($data['department_ids'], $data['designation_ids'], $data['user_ids']);

        return $data;
    }
}
