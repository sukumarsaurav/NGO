<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notices\Pages;

use App\Filament\Admin\Resources\Notices\NoticeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotice extends CreateRecord
{
    protected static string $resource = NoticeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['audience_filter'] = [
            'department_ids' => $data['department_ids'] ?? [],
            'designation_ids' => $data['designation_ids'] ?? [],
            'user_ids' => $data['user_ids'] ?? [],
        ];
        unset($data['department_ids'], $data['designation_ids'], $data['user_ids']);

        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}
