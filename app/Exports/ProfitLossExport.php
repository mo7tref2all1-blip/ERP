<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProfitLossExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected ?string $fromDate,
        protected ?string $toDate,
        protected ?int $branchId
    ) {}

    public function collection()
    {
        $rows = DB::table('customer_invoice_items')
            ->join('customer_invoices', 'customer_invoice_items.customer_invoice_id', '=', 'customer_invoices.id')
            ->join('products', 'customer_invoice_items.product_id', '=', 'products.id')
            ->whereNull('customer_invoices.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('customer_invoices.invoice_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('customer_invoices.invoice_date', '<=', $this->toDate))
            ->when($this->branchId, fn ($q) => $q->where('customer_invoices.branch_id', $this->branchId))
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

        return $rows->map(fn ($r) => [
            $r->name,
            number_format((float) $r->total_qty, 2),
            number_format((float) $r->total_revenue, 2),
            number_format((float) $r->total_cost, 2),
            number_format((float) $r->total_profit, 2),
        ]);
    }

    public function headings(): array
    {
        return ['الصنف', 'الكمية', 'الإيرادات (ج.م)', 'التكلفة (ج.م)', 'الربح (ج.م)'];
    }

    public function title(): string
    {
        return 'تقرير الأرباح والخسائر';
    }
}
