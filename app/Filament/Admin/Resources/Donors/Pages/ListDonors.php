<?php

namespace App\Filament\Admin\Resources\Donors\Pages;

use App\Filament\Admin\Resources\Donors\DonorResource;
use Filament\Resources\Pages\ListRecords;

class ListDonors extends ListRecords
{
    protected static string $resource = DonorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
