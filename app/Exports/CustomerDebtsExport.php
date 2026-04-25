<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CustomerDebtsExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function collection()
    {
        return Customer::whereNull('deleted_at')
            ->get()
            ->filter(fn ($c) => $c->balance > 0)
            ->values()
            ->map(fn ($c) => [
                $c->name,
                $c->phone ?? '',
                $c->type_label,
                number_format($c->total_invoices, 2),
                number_format($c->total_paid, 2),
                number_format($c->balance, 2),
            ]);
    }

    public function headings(): array
    {
        return ['العميل', 'الهاتف', 'النوع', 'إجمالي الفواتير (ج.م)', 'المدفوع (ج.م)', 'الرصيد المستحق (ج.م)'];
    }

    public function title(): string
    {
        return 'ديون العملاء';
    }
}
