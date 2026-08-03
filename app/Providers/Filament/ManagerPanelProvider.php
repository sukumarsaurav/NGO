<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * A second, wholly separate Filament panel rather than a permission-gated
 * /admin. See docs/modules/M11-manager-panel.md §"Why a separate panel":
 * permission-gating half of /admin leaks by default — every resource added
 * there is visible to managers until someone remembers to gate it.
 *
 * Resource query scopes and policies (the actual department-scoping) are added
 * in Sprint 13 alongside the first Manager resources — this provider only
 * establishes the panel boundary.
 */
class ManagerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('manager')
            ->path('manager')
            ->login()
            ->brandLogo(asset('images/branding/logo-horizontal.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon-32x32.png'))
            ->colors([
                'primary' => Color::hex('#1f7a4d'),
                'danger' => Color::hex('#c0392b'),
                'warning' => Color::hex('#945b14'),
                'success' => Color::hex('#1f7a4d'),
                'info' => Color::hex('#1f5f7a'),
            ])
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Manager/Resources'), for: 'App\Filament\Manager\Resources')
            ->discoverPages(in: app_path('Filament/Manager/Pages'), for: 'App\Filament\Manager\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Manager/Widgets'), for: 'App\Filament\Manager\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
