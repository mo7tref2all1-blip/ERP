<?php

namespace App\Filament\Widgets;

use App\Models\CustomerInvoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UnpaidInvoicesWidget extends BaseWidget
{
    protected ?string $heading = 'الفواتير غير المدفوعة (العملاء)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CustomerInvoice::with(['customer', 'branch'])
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->orderBy('invoice_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل'),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->getStateUsing(fn (CustomerInvoice $record) => $record->remaining_amount)
                    ->money('EGP')
                    ->color('danger'),
            ])
            ->paginated([10]);
    }
}
