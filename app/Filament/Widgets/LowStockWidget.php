<?php

namespace App\Filament\Widgets;

use App\Models\BranchStock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends BaseWidget
{
    protected ?string $heading = 'تحذير: أصناف وصلت الحد الأدنى للمخزون';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BranchStock::with(['product', 'branch'])
                    ->join('products', 'branch_stock.product_id', '=', 'products.id')
                    ->whereColumn('branch_stock.quantity', '<=', 'products.min_stock')
                    ->where('products.is_active', true)
                    ->where('products.min_stock', '>', 0)
                    ->select('branch_stock.*')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('الصنف')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية الحالية')
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('product.min_stock')
                    ->label('الحد الأدنى'),
                Tables\Columns\TextColumn::make('product.unit_label')
                    ->label('الوحدة'),
            ])
            ->paginated(false)
            ->defaultSort('quantity');
    }
}
