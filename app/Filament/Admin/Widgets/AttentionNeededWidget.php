<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Reports\AttentionNeededCounts;
use Filament\Widgets\Widget;

/**
 * "The most valuable widget on the page. It surfaces the failures that
 * otherwise sit silently in a log until a donor complains." See
 * docs/modules/M12-reports-analytics.md.
 */
class AttentionNeededWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.attention-needed';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{halted_subscriptions: int, failed_jobs: int, bounced_receipts: int, donors_missing_pan: int}
     */
    public function counts(): array
    {
        return app(AttentionNeededCounts::class)->get();
    }
}
