<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $navigationLabel = 'تقرير المخزون';
    protected static string $view = 'filament.pages.inventory-report';
    protected static ?int $navigationSort = 1;

    public ?int $branch_id = null;
    public ?int $category_id = null;
    public bool $low_stock_only = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = BranchStock::with(['product.category', 'branch'])
                    ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
                    ->when($this->category_id, fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('category_id', $this->category_id)))
                    ->when($this->low_stock_only, fn ($q) => $q->whereHas('product', fn ($pq) => $pq->whereColumn('branch_stock.quantity', '<=', 'products.min_stock')));
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('product.code')->label('الكود'),
                Tables\Columns\TextColumn::make('product.name')->label('الصنف')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('product.category.name')->label('التصنيف')->badge(),
                Tables\Columns\TextColumn::make('product.type')->label('النوع'),
                Tables\Columns\TextColumn::make('branch.name')->label('الفرع')->badge()->color('info'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable()
                    ->color(fn (BranchStock $record) => $record->quantity <= $record->product->min_stock ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('product.unit_label')->label('الوحدة'),
                Tables\Columns\TextColumn::make('product.min_stock')->label('الحد الأدنى'),
                Tables\Columns\TextColumn::make('product.default_price')->label('سعر البيع')->money('EGP'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('الفرع')
                    ->options(Branch::pluck('name', 'id')),
                Tables\Filters\Filter::make('low_stock')
                    ->label('مخزون منخفض فقط')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->whereHas('product', fn ($pq) => $pq->whereColumn('branch_stock.quantity', '<=', 'products.min_stock'))),
            ])
            ->defaultSort('product.name')
            ->paginated([25, 50, 100]);
    }
}
