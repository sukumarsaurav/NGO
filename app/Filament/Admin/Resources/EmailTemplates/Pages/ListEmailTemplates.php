<?php

namespace App\Filament\Admin\Resources\EmailTemplates\Pages;

use App\Filament\Admin\Resources\EmailTemplates\EmailTemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;
}
