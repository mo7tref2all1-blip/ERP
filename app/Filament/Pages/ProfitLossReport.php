<?php

namespace App\Filament\Pages;

use App\Exports\ProfitLossExport;
use App\Models\CustomerInvoice;
use App\Models\SupplierInvoice;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProfitLossReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $navigationLabel = 'تقرير الأرباح والخسائر';
    protected static string $view = 'filament.pages.profit-loss-report';
    protected static ?int $navigationSort = 2;

    public ?string $from_date = null;
    public ?string $to_date = null;
    public ?int $branch_id = null;

    public function mount(): void
    {
        $this->from_date = now()->startOfMonth()->toDateString();
        $this->to_date = now()->endOfMonth()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new ProfitLossExport($this->from_date, $this->to_date, $this->branch_id),
                    'profit-loss-' . date('Y-m-d') . '.xlsx'
                )),
        ];
    }

    public function getReportData(): array
    {
        $query = CustomerInvoice::query()
            ->when($this->from_date, fn ($q) => $q->whereDate('invoice_date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('invoice_date', '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id));

        $totalRevenue = $query->sum('net_amount');
        $totalCost = $query->sum('total_cost_fifo');
        $totalProfit = $query->sum('profit_amount');

        $invoicesByProduct = DB::table('customer_invoice_items')
            ->join('customer_invoices', 'customer_invoice_items.customer_invoice_id', '=', 'customer_invoices.id')
            ->join('products', 'customer_invoice_items.product_id', '=', 'products.id')
            ->when($this->from_date, fn ($q) => $q->whereDate('customer_invoices.invoice_date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('customer_invoices.invoice_date', '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('customer_invoices.branch_id', $this->branch_id))
            ->select(
                'products.name',
                DB::raw('SUM(customer_invoice_items.quantity) as total_qty'),
                DB::raw('SUM(customer_invoice_items.total) as total_revenue'),
                DB::raw('SUM(customer_invoice_items.cost_egp_fifo * customer_invoice_items.quantity) as total_cost'),
                DB::raw('SUM(customer_invoice_items.profit) as total_profit'),
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_profit')
            ->get();

        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
            'profit_margin' => $totalRevenue > 0 ? ($totalProfit / $totalRevenue * 100) : 0,
            'by_product' => $invoicesByProduct,
        ];
    }
}
