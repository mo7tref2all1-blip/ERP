<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SupplierDuesExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function collection()
    {
        return Supplier::whereNull('deleted_at')
            ->get()
            ->map(fn ($s) => [
                $s->name,
                $s->phone ?? '',
                number_format($s->total_invoices, 2),
                number_format($s->total_paid, 2),
                number_format($s->balance, 2),
            ]);
    }

    public function headings(): array
    {
        return ['المورد', 'الهاتف', 'إجمالي الفواتير (ج.م)', 'المدفوع (ج.م)', 'المستحق (ج.م)'];
    }

    public function title(): string
    {
        return 'مستحقات الموردين';
    }
}
