<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>فاتورة بيع - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; direction: rtl; }
        .header { background: #8B6914; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 22px; }
        .header .invoice-info { text-align: left; }
        .content { padding: 20px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .party { width: 48%; padding: 12px; background: #f9f9f9; border-radius: 8px; border: 1px solid #eee; }
        .party h3 { color: #8B6914; margin-bottom: 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #8B6914; color: white; padding: 8px; text-align: right; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fafafa; }
        .totals { width: 300px; margin-right: auto; }
        .totals table td { padding: 6px 8px; }
        .totals .total-row { font-weight: bold; font-size: 14px; background: #8B6914; color: white; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #8B6914; text-align: center; color: #666; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ \App\Models\Setting::get('company_name', 'شركة الأخشاب') }}</h1>
            <p>{{ \App\Models\Setting::get('company_address') }}</p>
            <p>{{ \App\Models\Setting::get('company_phone') }}</p>
        </div>
        <div class="invoice-info">
            <h2 style="font-size:18px; margin-bottom:5px;">فاتورة بيع</h2>
            <p>رقم: {{ $invoice->invoice_number }}</p>
            <p>التاريخ: {{ $invoice->invoice_date->format('d/m/Y') }}</p>
            @if($invoice->due_date)
            <p>الاستحقاق: {{ $invoice->due_date->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    <div class="content">
        <div class="parties">
            <div class="party">
                <h3>بيانات العميل</h3>
                <p><strong>{{ $invoice->customer->name }}</strong></p>
                @if($invoice->customer->phone) <p>هاتف: {{ $invoice->customer->phone }}</p> @endif
                @if($invoice->customer->address) <p>عنوان: {{ $invoice->customer->address }}</p> @endif
            </div>
            <div class="party">
                <h3>بيانات الفرع</h3>
                <p><strong>{{ $invoice->branch->name }}</strong></p>
                <p>حالة الدفع:
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ $invoice->status_label }}
                    </span>
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الصنف</th>
                    <th>الكمية</th>
                    <th>الوحدة</th>
                    <th>سعر الوحدة</th>
                    <th>الخصم</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $item->product->unit_label }}</td>
                    <td>{{ number_format($item->unit_price, 2) }} ج.م</td>
                    <td>{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '-' }}</td>
                    <td>{{ number_format($item->total, 2) }} ج.م</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr><td>إجمالي الأصناف:</td><td>{{ number_format($invoice->total_amount, 2) }} ج.م</td></tr>
                @if($invoice->discount_amount > 0)
                <tr><td>الخصم:</td><td style="color:#dc2626;">{{ number_format($invoice->discount_amount, 2) }} ج.م</td></tr>
                @endif
                <tr class="total-row"><td>صافي الفاتورة:</td><td>{{ number_format($invoice->net_amount, 2) }} ج.م</td></tr>
                @if($invoice->paid_amount > 0)
                <tr><td style="color:#16a34a;">المدفوع:</td><td style="color:#16a34a;">{{ number_format($invoice->paid_amount, 2) }} ج.م</td></tr>
                <tr><td style="color:#dc2626; font-weight:bold;">المتبقي:</td><td style="color:#dc2626; font-weight:bold;">{{ number_format($invoice->remaining_amount, 2) }} ج.م</td></tr>
                @endif
            </table>
        </div>

        @if($invoice->notes)
        <div style="margin-top:20px; padding:12px; background:#f9f9f9; border-radius:8px;">
            <strong>ملاحظات:</strong> {{ $invoice->notes }}
        </div>
        @endif
    </div>

    <div class="footer">
        <p>{{ \App\Models\Setting::get('invoice_footer_text', 'شكراً لتعاملكم معنا') }}</p>
        <p style="margin-top:5px; font-size:10px; color:#999;">طُبع في: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
