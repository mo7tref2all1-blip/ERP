<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>فاتورة بيع - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #222; direction: rtl; background: #fff; }

        /* ===== HEADER ===== */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .header-wrap { background: linear-gradient(135deg, #7a5c10 0%, #b8861e 100%); color: #fff; padding: 18px 20px; }
        .logo-cell { width: 22%; vertical-align: middle; text-align: right; }
        .logo-cell img { max-width: 120px; max-height: 70px; }
        .company-cell { width: 44%; vertical-align: middle; text-align: center; }
        .company-name { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 4px; }
        .company-sub { font-size: 10px; opacity: 0.85; line-height: 1.6; }
        .invoice-meta-cell { width: 34%; vertical-align: middle; text-align: left; }
        .invoice-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.4); padding-bottom: 4px; margin-bottom: 6px; }
        .invoice-meta-cell p { font-size: 11px; line-height: 1.7; }

        /* ===== STATUS STRIP ===== */
        .status-strip { padding: 6px 20px; font-size: 11px; display: flex; justify-content: space-between; align-items: center; }
        .status-strip.paid   { background: #d1fae5; color: #065f46; border-bottom: 2px solid #6ee7b7; }
        .status-strip.partial { background: #fef3c7; color: #78350f; border-bottom: 2px solid #fcd34d; }
        .status-strip.unpaid  { background: #fee2e2; color: #991b1b; border-bottom: 2px solid #fca5a5; }
        .status-stamp { font-size: 14px; font-weight: bold; }

        /* ===== PARTIES ===== */
        .content { padding: 16px 20px; }
        .parties-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .party-cell { width: 49%; vertical-align: top; }
        .party-box { padding: 10px 12px; background: #fafafa; border: 1px solid #e5e7eb; border-radius: 6px; }
        .party-box h3 { color: #7a5c10; font-size: 12px; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .party-box p { font-size: 11px; line-height: 1.7; color: #444; }

        /* ===== ITEMS TABLE ===== */
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
        table.items thead tr { background: #7a5c10; color: #fff; }
        table.items th { padding: 7px 8px; text-align: right; font-weight: normal; }
        table.items tbody tr:nth-child(even) { background: #fdf8ee; }
        table.items tbody tr:hover { background: #fef3c7; }
        table.items td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; color: #333; }
        table.items tbody tr:last-child td { border-bottom: 2px solid #b8861e; }

        /* ===== TOTALS ===== */
        .totals-wrap { width: 280px; float: left; }
        table.totals { width: 100%; border-collapse: collapse; font-size: 11px; }
        table.totals td { padding: 5px 8px; }
        table.totals tr:nth-child(even) { background: #fafafa; }
        table.totals .total-final td { background: #7a5c10; color: #fff; font-weight: bold; font-size: 13px; padding: 7px 8px; }
        .clearfix::after { content: ''; display: table; clear: both; }

        /* ===== NOTES ===== */
        .notes-box { margin-top: 14px; padding: 10px 12px; background: #fafafa; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 11px; }

        /* ===== FOOTER ===== */
        .footer { margin-top: 24px; padding: 12px 20px; border-top: 2px solid #b8861e; text-align: center; color: #666; font-size: 10px; }
        .footer p { line-height: 1.8; }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header-wrap">
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="logo">
                @endif
            </td>
            <td class="company-cell">
                <div class="company-name">{{ \App\Models\Setting::get('company_name', 'شركة الأخشاب') }}</div>
                <div class="company-sub">
                    {{ \App\Models\Setting::get('company_address') }}<br>
                    هاتف: {{ \App\Models\Setting::get('company_phone') }}
                    @if(\App\Models\Setting::get('company_email'))
                        &nbsp;|&nbsp; {{ \App\Models\Setting::get('company_email') }}
                    @endif
                    @if(\App\Models\Setting::get('tax_number'))
                        <br>الرقم الضريبي: {{ \App\Models\Setting::get('tax_number') }}
                    @endif
                </div>
            </td>
            <td class="invoice-meta-cell">
                <div class="invoice-title">فاتورة بيع</div>
                <p>رقم الفاتورة: <strong>{{ $invoice->invoice_number }}</strong></p>
                <p>التاريخ: {{ $invoice->invoice_date->format('d/m/Y') }}</p>
                @if($invoice->due_date)
                    <p>الاستحقاق: {{ $invoice->due_date->format('d/m/Y') }}</p>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- STATUS STRIP --}}
<div class="status-strip {{ $invoice->status }}">
    <span>حالة الدفع: <strong>{{ $invoice->status_label }}</strong></span>
    @if($invoice->status === 'paid')
        <span class="status-stamp">✓ مدفوعة بالكامل</span>
    @elseif($invoice->status === 'partial')
        <span class="status-stamp">◑ مدفوعة جزئياً</span>
    @else
        <span class="status-stamp">✗ غير مدفوعة</span>
    @endif
</div>

<div class="content">

    {{-- PARTIES --}}
    <table class="parties-table">
        <tr>
            <td class="party-cell" style="padding-left:8px;">
                <div class="party-box">
                    <h3>بيانات العميل</h3>
                    <p><strong>{{ $invoice->customer->name }}</strong></p>
                    @if($invoice->customer->phone) <p>هاتف: {{ $invoice->customer->phone }}</p> @endif
                    @if($invoice->customer->address) <p>عنوان: {{ $invoice->customer->address }}</p> @endif
                    @if($invoice->customer->tax_number) <p>رقم ضريبي: {{ $invoice->customer->tax_number }}</p> @endif
                </div>
            </td>
            <td class="party-cell" style="padding-right:8px;">
                <div class="party-box">
                    <h3>الفرع</h3>
                    <p><strong>{{ $invoice->branch->name }}</strong></p>
                    @if($invoice->notes) <p>ملاحظة: {{ $invoice->notes }}</p> @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ITEMS --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:34%">الصنف</th>
                <th style="width:10%">الكمية</th>
                <th style="width:8%">الوحدة</th>
                <th style="width:14%">سعر الوحدة</th>
                <th style="width:10%">الخصم</th>
                <th style="width:20%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $item->product->name }}</strong></td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->product->unit_label }}</td>
                <td>{{ number_format($item->unit_price, 2) }} ج.م</td>
                <td>{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '—' }}</td>
                <td><strong>{{ number_format($item->total, 2) }} ج.م</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div class="clearfix">
        <div class="totals-wrap">
            <table class="totals">
                <tr><td>إجمالي الأصناف:</td><td>{{ number_format($invoice->total_amount, 2) }} ج.م</td></tr>
                @if($invoice->discount_amount > 0)
                <tr><td style="color:#dc2626;">الخصم:</td><td style="color:#dc2626;">{{ number_format($invoice->discount_amount, 2) }} ج.م</td></tr>
                @endif
                <tr class="total-final"><td>صافي الفاتورة:</td><td>{{ number_format($invoice->net_amount, 2) }} ج.م</td></tr>
                @if($invoice->paid_amount > 0)
                <tr><td style="color:#16a34a;">المدفوع:</td><td style="color:#16a34a;">{{ number_format($invoice->paid_amount, 2) }} ج.م</td></tr>
                <tr><td style="color:#dc2626;font-weight:bold;">المتبقي:</td><td style="color:#dc2626;font-weight:bold;">{{ number_format($invoice->remaining_amount, 2) }} ج.م</td></tr>
                @endif
            </table>
        </div>
    </div>

    @if($invoice->notes)
    <div class="notes-box"><strong>ملاحظات:</strong> {{ $invoice->notes }}</div>
    @endif

</div>

<div class="footer">
    <p>{{ \App\Models\Setting::get('invoice_footer_text', 'شكراً لتعاملكم معنا — يسعدنا خدمتكم دائماً') }}</p>
    <p style="color:#aaa; margin-top:4px;">طُبع في: {{ now()->format('d/m/Y H:i') }}</p>
</div>

</body>
</html>
