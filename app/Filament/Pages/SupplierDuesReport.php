<?php

namespace App\Filament\Pages;

use App\Exports\SupplierDuesExport;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class SupplierDuesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $navigationLabel = 'مستحقات الموردين';
    protected static string $view = 'filament.pages.supplier-dues-report';
    protected static ?int $navigationSort = 4;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(new SupplierDuesExport(), 'supplier-dues-' . date('Y-m-d') . '.xlsx')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Supplier::withSum('invoices as total_invoices_sum', 'total_amount')
                    ->withSum('payments as total_payments_sum', 'amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('المورد')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف'),
                Tables\Columns\TextColumn::make('total_invoices')
                    ->label('إجمالي الفواتير')
                    ->getStateUsing(fn (Supplier $record) => $record->total_invoices)
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('المدفوع')
                    ->getStateUsing(fn (Supplier $record) => $record->total_paid)
                    ->money('EGP')
                    ->color('success'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('المستحق')
                    ->getStateUsing(fn (Supplier $record) => $record->balance)
                    ->money('EGP')
                    ->color(fn (Supplier $record) => $record->balance > 0 ? 'danger' : 'success')
                    ->weight('bold'),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([25, 50]);
    }
}
