<?php

namespace App\Filament\Admin\Resources\Receipts\Pages;

use App\Filament\Admin\Resources\Receipts\ReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
