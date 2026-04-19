<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AccountsOverviewWidget;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UnpaidInvoicesWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->colors([
                'primary' => Color::hex(\App\Models\Setting::get('primary_color', '#8B6914')),
                'gray' => Color::Zinc,
            ])
            ->font('Cairo')
            ->brandName(fn () => \App\Models\Setting::get('company_name', 'نظام ERP'))
            ->brandLogo(fn () => \App\Models\Setting::get('logo_url'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                \Filament\Pages\Dashboard::class,
                \App\Filament\Pages\GeneralSettings::class,
                \App\Filament\Pages\Settings::class,
                \App\Filament\Pages\SystemUpdate::class,
                \App\Filament\Pages\StockTransfer::class,
                \App\Filament\Pages\InventoryReport::class,
                \App\Filament\Pages\ProfitLossReport::class,
                \App\Filament\Pages\CustomerDebtsReport::class,
                \App\Filament\Pages\SupplierDuesReport::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                LowStockWidget::class,
                UnpaidInvoicesWidget::class,
                AccountsOverviewWidget::class,
                RevenueChartWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('المخزون والمنتجات')
                    ->icon('heroicon-o-archive-box')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('المبيعات')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('المشتريات والموردون')
                    ->icon('heroicon-o-truck')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('الحسابات والمالية')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('التقارير')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('الإعدادات')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
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
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns(['default' => 1, 'sm' => 2, 'lg' => 4]),
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k']);
    }
}
