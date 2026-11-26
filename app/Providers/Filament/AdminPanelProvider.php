<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ChangePassword;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\TableMapping;
use App\Http\Middleware\CheckRoleAccess;
use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login(Login::class)
            ->colors(fn () => [
                'primary' => Color::hex(Setting::get()->primary_color ?: '#2f76b5'),
            ])
            ->darkMode(true)
            ->brandName(fn () => Setting::get()->app_name ?: 'Guest Management')
            ->brandLogo(function () {
                $settings = Setting::get();
                if ($logoUrl = $settings->getLogoUrl()) {
                    return new HtmlString(
                        '<img src="'.e($logoUrl).'" alt="'.e($settings->app_name ?: 'Logo').'" style="height: 40px; max-width: 180px; object-fit: contain;">'
                    );
                }

                return null;
            })
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => Setting::get()->getFaviconUrl())
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.components.theme-styles', ['settings' => Setting::get()])
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.components.ownership-footer', ['compact' => false])
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => view('filament.components.ownership-footer', ['compact' => true])
            )
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                ChangePassword::class,
                TableMapping::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Ubah Password')
                    ->url(fn (): string => ChangePassword::getUrl())
                    ->icon('heroicon-o-key')
                    ->sort(1),
            ])
            ->navigationGroups([
                NavigationGroup::make('Operasional Acara'),
                NavigationGroup::make('Master & Pengaturan'),
            ])
            ->navigationItems([
                NavigationItem::make('Registrasi Tamu')
                    ->url('/scan')
                    ->icon('heroicon-o-qr-code')
                    ->group('Operasional Acara')
                    ->sort(3)
                    ->visible(fn () => auth()->check() && auth()->user()->role === 'admin'),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                CheckRoleAccess::class,
            ]);
    }
}
