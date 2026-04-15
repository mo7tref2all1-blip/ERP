<x-filament-panels::page>
    <div class="flex gap-4 mb-6 flex-wrap">
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">من تاريخ</label>
            <input type="date" wire:model.live="from_date" class="block mt-1 rounded-lg border-gray-300 shadow-sm" />
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">إلى تاريخ</label>
            <input type="date" wire:model.live="to_date" class="block mt-1 rounded-lg border-gray-300 shadow-sm" />
        </div>
    </div>

    @php $data = $this->getReportData(); @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-primary-500">
            <div class="text-sm text-gray-500">إجمالي الإيرادات</div>
            <div class="text-2xl font-bold text-primary-600">{{ number_format($data['total_revenue'], 2) }} ج.م</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-red-500">
            <div class="text-sm text-gray-500">إجمالي التكلفة (FIFO)</div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($data['total_cost'], 2) }} ج.م</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-green-500">
            <div class="text-sm text-gray-500">إجمالي الأرباح</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($data['total_profit'], 2) }} ج.م</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-yellow-500">
            <div class="text-sm text-gray-500">هامش الربح</div>
            <div class="text-2xl font-bold text-yellow-600">{{ number_format($data['profit_margin'], 1) }}%</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="p-4 border-b font-semibold text-gray-700 dark:text-gray-300">
            تفصيل الأرباح حسب الصنف
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-right text-gray-600">الصنف</th>
                    <th class="px-4 py-3 text-right text-gray-600">الكمية المباعة</th>
                    <th class="px-4 py-3 text-right text-gray-600">الإيراد</th>
                    <th class="px-4 py-3 text-right text-gray-600">التكلفة</th>
                    <th class="px-4 py-3 text-right text-gray-600">الربح</th>
                    <th class="px-4 py-3 text-right text-gray-600">الهامش</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['by_product'] as $row)
                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3 font-medium">{{ $row->name }}</td>
                    <td class="px-4 py-3">{{ number_format($row->total_qty, 2) }}</td>
                    <td class="px-4 py-3">{{ number_format($row->total_revenue, 2) }} ج.م</td>
                    <td class="px-4 py-3 text-red-600">{{ number_format($row->total_cost, 2) }} ج.م</td>
                    <td class="px-4 py-3 font-bold text-green-600">{{ number_format($row->total_profit, 2) }} ج.م</td>
                    <td class="px-4 py-3">
                        @if($row->total_revenue > 0)
                            {{ number_format($row->total_profit / $row->total_revenue * 100, 1) }}%
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
