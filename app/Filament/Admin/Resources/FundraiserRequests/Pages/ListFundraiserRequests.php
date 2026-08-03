<?php

namespace App\Filament\Admin\Resources\FundraiserRequests\Pages;

use App\Filament\Admin\Resources\FundraiserRequests\FundraiserRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListFundraiserRequests extends ListRecords
{
    protected static string $resource = FundraiserRequestResource::class;
}
