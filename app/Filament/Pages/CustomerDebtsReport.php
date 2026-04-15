<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerDebtsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $navigationLabel = 'ديون العملاء';
    protected static string $view = 'filament.pages.customer-debts-report';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::withSum('invoices as total_invoices_sum', 'net_amount')
                    ->withSum('payments as total_payments_sum', 'amount')
                    ->having(
                        \DB::raw('(COALESCE(opening_balance, 0) + COALESCE(total_invoices_sum, 0) - COALESCE(total_payments_sum, 0))'),
                        '>',
                        0
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('العميل')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => $state === 'company' ? 'شركة' : 'فرد')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_invoices_sum')
                    ->label('إجمالي الفواتير')
                    ->getStateUsing(fn (Customer $record) => $record->total_invoices)
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('المدفوع')
                    ->getStateUsing(fn (Customer $record) => $record->total_paid)
                    ->money('EGP')
                    ->color('success'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('الرصيد المستحق')
                    ->getStateUsing(fn (Customer $record) => $record->balance)
                    ->money('EGP')
                    ->color('danger')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('oldest_unpaid_invoice')
                    ->label('أقدم فاتورة غير مدفوعة')
                    ->getStateUsing(fn (Customer $record) => $record->invoices()
                        ->where('status', '!=', 'paid')
                        ->orderBy('invoice_date')
                        ->value('invoice_date'))
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع العميل')
                    ->options(['individual' => 'فرد', 'company' => 'شركة']),
            ])
            ->defaultSort('balance', 'desc')
            ->paginated([25, 50]);
    }
}
