<?php

namespace App\Providers\Filament;

use App\Filament\Central\Widgets\TenantStats;
use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The "operator" panel: lives ONLY on the central domain and has no tenancy
 * at all — no TenancyPlugin, no tenant switcher, no per-tenant database.
 * Everything it shows (tenants, domains, users) is central data.
 *
 * The plugin's host middleware never initializes tenancy on the central host,
 * so ordinary Eloquent here always hits the central connection. Pinning the
 * panel to that host (->domain()) keeps it from even routing on tenant hosts.
 */
class CentralPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('central')
            ->path('central')
            ->domain(config('packstub-tenancy.central_domain'))
            ->login(Login::class) // same pre-filled demo login as the admin panel
            ->brandName('Tenancy Demo · Operator')
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverResources(in: app_path('Filament/Central/Resources'), for: 'App\Filament\Central\Resources')
            ->discoverPages(in: app_path('Filament/Central/Pages'), for: 'App\Filament\Central\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Central/Widgets'), for: 'App\Filament\Central\Widgets')
            ->widgets([
                TenantStats::class,
                AccountWidget::class,
            ])
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
