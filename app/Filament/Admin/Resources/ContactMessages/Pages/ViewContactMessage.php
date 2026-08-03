<?php

namespace App\Filament\Admin\Resources\ContactMessages\Pages;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var ContactMessage $contactMessage */
        $contactMessage = $this->getRecord();

        if (! $contactMessage->is_read) {
            $contactMessage->update(['is_read' => true, 'read_at' => now()]);
        }
    }
}
