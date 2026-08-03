<?php

namespace App\Filament\Manager\Resources\Donations\Pages;

use App\Filament\Manager\Resources\Donations\DonationResource;
use Filament\Resources\Pages\ListRecords;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;
}
