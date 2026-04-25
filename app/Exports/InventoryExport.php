<?php

namespace App\Exports;

use App\Models\BranchStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InventoryExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function collection()
    {
        return BranchStock::with(['product.category', 'branch'])
            ->get()
            ->map(fn ($s) => [
                $s->product?->code ?? '',
                $s->product?->name ?? '',
                $s->product?->category?->name ?? '',
                $s->product?->type ?? '',
                $s->branch?->name ?? '',
                (float) $s->quantity,
                $s->product?->unit_label ?? '',
                (float) ($s->product?->min_stock ?? 0),
                (float) ($s->product?->default_price ?? 0),
            ]);
    }

    public function headings(): array
    {
        return ['الكود', 'الصنف', 'التصنيف', 'النوع', 'الفرع', 'الكمية', 'الوحدة', 'الحد الأدنى', 'سعر البيع (ج.م)'];
    }

    public function title(): string
    {
        return 'تقرير المخزون';
    }
}
