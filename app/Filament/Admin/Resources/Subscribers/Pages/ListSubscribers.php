<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscribers\Pages;

use App\Filament\Admin\Resources\Subscribers\SubscriberResource;
use App\Models\Subscriber;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => $this->exportCsv()),
        ];
    }

    private function exportCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'Name', 'Confirmed at', 'Unsubscribed at', 'Source', 'Subscribed at']);

            Subscriber::query()->orderBy('created_at')->each(function (Subscriber $subscriber) use ($handle): void {
                fputcsv($handle, [
                    $subscriber->email,
                    $subscriber->name,
                    $subscriber->confirmed_at?->toDateTimeString(),
                    $subscriber->unsubscribed_at?->toDateTimeString(),
                    $subscriber->source,
                    $subscriber->created_at?->toDateTimeString(),
                ]);
            });

            fclose($handle);
        }, 'subscribers.csv');
    }
}
