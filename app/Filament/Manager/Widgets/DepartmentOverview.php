<?php

declare(strict_types=1);

namespace App\Filament\Manager\Widgets;

use App\Enums\MemberStatus;
use App\Models\IssuedDocument;
use App\Models\Member;
use App\Models\Notice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * "No donation figures beyond an org-wide read-only view — managers manage
 * people, not money." See docs/modules/M11-manager-panel.md's "Manager
 * dashboard" section for the five numbers this surfaces.
 */
class DepartmentOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $departmentIds = Auth::user()->managedDepartmentIds();

        $activeCount = Member::query()
            ->whereIn('department_id', $departmentIds)
            ->where('status', MemberStatus::Active->value)
            ->count();

        $pendingCount = Member::query()
            ->whereIn('department_id', $departmentIds)
            ->where('status', MemberStatus::Pending->value)
            ->count();

        $documentsThisMonth = IssuedDocument::query()
            ->whereHas('member', fn ($q) => $q->whereIn('department_id', $departmentIds))
            ->where('issued_on', '>=', now()->startOfMonth())
            ->count();

        $expiringSoon = Member::query()
            ->whereIn('department_id', $departmentIds)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $recentNotices = Notice::query()
            ->where('created_by_user_id', Auth::id())
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            Stat::make('Active members', (string) $activeCount),
            Stat::make('Pending approvals', (string) $pendingCount)
                ->color($pendingCount > 0 ? 'warning' : 'success'),
            Stat::make('Documents issued this month', (string) $documentsThisMonth),
            Stat::make('ID cards expiring in 30 days', (string) $expiringSoon)
                ->color($expiringSoon > 0 ? 'danger' : 'success'),
            Stat::make('Notices published (30 days)', (string) $recentNotices),
        ];
    }
}
