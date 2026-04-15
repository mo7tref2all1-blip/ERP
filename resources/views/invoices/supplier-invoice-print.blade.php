<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>فاتورة شراء - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; direction: rtl; }
        .header { background: #1e40af; color: white; padding: 20px; display: flex; justify-content: space-between; }
        .header h1 { font-size: 22px; }
        .content { padding: 20px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .party { width: 48%; padding: 12px; background: #f9f9f9; border-radius: 8px; border: 1px solid #eee; }
        .party h3 { color: #1e40af; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #1e40af; color: white; padding: 8px; text-align: right; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .totals { width: 300px; margin-right: auto; }
        .total-row { font-weight: bold; background: #1e40af; color: white; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #1e40af; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ \App\Models\Setting::get('company_name', 'شركة الأخشاب') }}</h1>
            <p>{{ \App\Models\Setting::get('company_phone') }}</p>
        </div>
        <div>
            <h2>فاتورة شراء</h2>
            <p>رقم: {{ $invoice->invoice_number }}</p>
            <p>التاريخ: {{ $invoice->invoice_date->format('d/m/Y') }}</p>
        </div>
    </div>
    <div class="content">
        <div class="parties">
            <div class="party">
                <h3>بيانات المورد</h3>
                <p><strong>{{ $invoice->supplier->name }}</strong></p>
                @if($invoice->supplier->phone) <p>هاتف: {{ $invoice->supplier->phone }}</p> @endif
                @if($invoice->supplier->address) <p>عنوان: {{ $invoice->supplier->address }}</p> @endif
                @if($invoice->supplier->bank_account) <p>الحساب البنكي: {{ $invoice->supplier->bank_account }}</p> @endif
            </div>
            <div class="party">
                <h3>الفرع المستلم</h3>
                <p><strong>{{ $invoice->branch->name }}</strong></p>
                <p>الحالة: {{ $invoice->status_label }}</p>
                @if($invoice->due_date) <p>الاستحقاق: {{ $invoice->due_date->format('d/m/Y') }}</p> @endif
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الصنف</th>
                    <th>الكمية</th>
                    <th>الوحدة</th>
                    <th>التكلفة (جنيه)</th>
                    @if($invoice->items->contains(fn($i) => $i->cost_usd)) <th>التكلفة (دولار)</th><th>سعر الصرف</th> @endif
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
                    <td>{{ number_format($item->cost_egp, 4) }} ج.م</td>
                    @if($invoice->items->contains(fn($it) => $it->cost_usd))
                    <td>{{ $item->cost_usd ? '$' . number_format($item->cost_usd, 2) : '-' }}</td>
                    <td>{{ $item->dollar_rate ? number_format($item->dollar_rate, 2) : '-' }}</td>
                    @endif
                    <td>{{ number_format($item->total_egp, 2) }} ج.م</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals">
            <table>
                <tr class="total-row"><td>إجمالي الفاتورة:</td><td>{{ number_format($invoice->total_amount, 2) }} ج.م</td></tr>
                @if($invoice->paid_amount > 0)
                <tr><td style="color:#16a34a;">المدفوع:</td><td style="color:#16a34a;">{{ number_format($invoice->paid_amount, 2) }} ج.م</td></tr>
                <tr><td style="color:#dc2626; font-weight:bold;">المتبقي:</td><td style="color:#dc2626; font-weight:bold;">{{ number_format($invoice->remaining_amount, 2) }} ج.م</td></tr>
                @endif
            </table>
        </div>
    </div>
    <div class="footer">
        <p>{{ \App\Models\Setting::get('invoice_footer_text', 'شكراً لتعاملكم معنا') }}</p>
        <p style="margin-top:5px; font-size:10px;">طُبع في: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
