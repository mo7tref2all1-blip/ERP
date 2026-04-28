<?php
namespace App\Filament\Pages;
use App\Exports\CustomerDebtsExport;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
class CustomerDebtsReport extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-user-minus';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $navigationLabel = 'ديون العملاء';
    protected static string $view = 'filament.pages.customer-debts-report';
    protected static ?int $navigationSort = 3;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(new CustomerDebtsExport(), 'customer-debts-' . date('Y-m-d') . '.xlsx')),
        ];
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::selectRaw('customers.*,
                    COALESCE((SELECT SUM(net_amount) FROM customer_invoices WHERE customer_id = customers.id AND deleted_at IS NULL), 0) as total_invoices_sum,
                    COALESCE((SELECT SUM(amount) FROM customer_payments WHERE customer_id = customers.id), 0) as total_payments_sum,
                    COALESCE(opening_balance, 0) + COALESCE((SELECT SUM(net_amount) FROM customer_invoices WHERE customer_id = customers.id AND deleted_at IS NULL), 0) - COALESCE((SELECT SUM(amount) FROM customer_payments WHERE customer_id = customers.id), 0) as balance_computed
                ')
                ->whereNull('customers.deleted_at')
                ->whereRaw('(COALESCE(opening_balance, 0) + COALESCE((SELECT SUM(net_amount) FROM customer_invoices WHERE customer_id = customers.id AND deleted_at IS NULL), 0) - COALESCE((SELECT SUM(amount) FROM customer_payments WHERE customer_id = customers.id), 0)) > 0')
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
                Tables\Columns\TextColumn::make('balance_computed')
                    ->label('الرصيد المستحق')
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
            ->defaultSort('balance_computed', 'desc')
            ->paginated([25, 50]);
    }
}
