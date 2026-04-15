<?php

namespace App\Filament\Widgets;

use App\Models\CustomerInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'الإيرادات والأرباح - آخر 6 أشهر';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $revenues = $months->map(fn (Carbon $month) => CustomerInvoice::whereYear('invoice_date', $month->year)
            ->whereMonth('invoice_date', $month->month)
            ->sum('net_amount')
        );

        $profits = $months->map(fn (Carbon $month) => CustomerInvoice::whereYear('invoice_date', $month->year)
            ->whereMonth('invoice_date', $month->month)
            ->sum('profit_amount')
        );

        return [
            'datasets' => [
                [
                    'label' => 'الإيرادات',
                    'data' => $revenues->values()->toArray(),
                    'backgroundColor' => 'rgba(139, 105, 20, 0.2)',
                    'borderColor' => '#8B6914',
                    'fill' => true,
                ],
                [
                    'label' => 'الأرباح',
                    'data' => $profits->values()->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => '#22C55E',
                    'fill' => true,
                ],
            ],
            'labels' => $months->map(fn (Carbon $m) => $m->translatedFormat('M Y'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
