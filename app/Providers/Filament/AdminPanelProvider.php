<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\VpnMarketInfoWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\FontProviders\LocalFontProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Nwidart\Modules\Facades\Module;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // تنظیمات اصلی پنل
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo, // رنگ مدرن‌تر لاجوردی
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop() // نوار کناری جمع‌شونده مدرن
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('فروش و مالی')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('مدیریت کاربران')
                    ->icon('heroicon-o-users')
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('محتوا و ربات')
                    ->icon('heroicon-o-document-text')
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('سیستم و سرورها')
                    ->icon('heroicon-o-server')
                    ->collapsed(),
            ])
            ->font(
                'Vaz',
                url: asset('css/font.css'),
                provider: LocalFontProvider::class,
            )
            ->assets([
                \Filament\Support\Assets\Css::make('custom-admin-css', asset('css/custom-admin.css?v=3.0')),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                VpnMarketInfoWidget::class,
                \App\Filament\Widgets\QuickActionsWidget::class,
                \App\Filament\Widgets\ServerStatusWidget::class,
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\OrdersChart::class,
                \App\Filament\Widgets\UsersChart::class,
            ])
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
            ]);

        // ----------------------------------------------------------
        // لود کردن خودکار ریسورس‌های ماژول‌ها (Blog, Referral, ...)
        // ----------------------------------------------------------
        foreach (Module::getOrdered() as $module) {
            if ($module->isEnabled()) {
                $moduleName = $module->getName();
                $modulePath = $module->getPath();


                if (is_dir($modulePath . '/Filament/Resources')) {
                    $panel->discoverResources(
                        in: $modulePath . '/Filament/Resources',
                        for: "Modules\\{$moduleName}\\Filament\\Resources"
                    );
                }


                if (is_dir($modulePath . '/Filament/Pages')) {
                    $panel->discoverPages(
                        in: $modulePath . '/Filament/Pages',
                        for: "Modules\\{$moduleName}\\Filament\\Pages"
                    );
                }

                // لود کردن Widgets (ویجت‌های داشبورد ماژول)
                if (is_dir($modulePath . '/Filament/Widgets')) {
                    $panel->discoverWidgets(
                        in: $modulePath . '/Filament/Widgets',
                        for: "Modules\\{$moduleName}\\Filament\\Widgets"
                    );
                }


                if (is_dir($modulePath . '/Filament/Clusters')) {
                    $panel->discoverClusters(
                        in: $modulePath . '/Filament/Clusters',
                        for: "Modules\\{$moduleName}\\Filament\\Clusters"
                    );
                }
            }
        }

        return $panel;
    }
}
