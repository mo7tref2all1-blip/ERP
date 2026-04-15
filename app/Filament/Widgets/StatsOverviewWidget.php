<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $unpaidSalesTotal = CustomerInvoice::whereIn('status', ['unpaid', 'partial'])->sum('net_amount')
            - CustomerInvoice::whereIn('status', ['unpaid', 'partial'])->sum('paid_amount');

        $unpaidPurchasesTotal = SupplierInvoice::whereIn('status', ['unpaid', 'partial'])->sum('total_amount')
            - SupplierInvoice::whereIn('status', ['unpaid', 'partial'])->sum('paid_amount');

        $thisMonthRevenue = CustomerInvoice::whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('net_amount');

        $thisMonthProfit = CustomerInvoice::whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('profit_amount');

        return [
            Stat::make('إيرادات هذا الشهر', number_format($thisMonthRevenue, 2) . ' ج.م')
                ->description('فواتير البيع لهذا الشهر')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('أرباح هذا الشهر', number_format($thisMonthProfit, 2) . ' ج.م')
                ->description('صافي الربح بعد التكلفة FIFO')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('ديون العملاء', number_format($unpaidSalesTotal, 2) . ' ج.م')
                ->description('إجمالي الفواتير غير المسددة')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning')
                ->icon('heroicon-o-users'),

            Stat::make('مستحقات الموردين', number_format($unpaidPurchasesTotal, 2) . ' ج.م')
                ->description('إجمالي فواتير الشراء المستحقة')
                ->descriptionIcon('heroicon-m-truck')
                ->color('danger')
                ->icon('heroicon-o-truck'),
        ];
    }

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }
}
