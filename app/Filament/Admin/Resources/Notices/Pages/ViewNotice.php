<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notices\Pages;

use App\Filament\Admin\Resources\Notices\NoticeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewNotice extends ViewRecord
{
    protected static string $resource = NoticeResource::class;
}
