<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'المخزون والمنتجات';
    protected static ?string $navigationLabel = 'أصناف الخشب';
    protected static ?string $modelLabel = 'صنف';
    protected static ?string $pluralModelLabel = 'الأصناف';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('المعلومات الأساسية')->schema([
                Forms\Components\TextInput::make('code')
                    ->label('كود الصنف')
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                Forms\Components\TextInput::make('name')
                    ->label('اسم الصنف')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->label('اسم التصنيف')->required(),
                    ]),
                Forms\Components\TextInput::make('type')
                    ->label('نوع الخشب')
                    ->placeholder('مثال: صنوبر، بلوط، زان')
                    ->maxLength(100),
            ])->columns(2),

            Forms\Components\Section::make('المواصفات')->schema([
                Forms\Components\TextInput::make('thickness_mm')
                    ->label('السمك (مم)')
                    ->numeric()
                    ->minValue(0),
                Forms\Components\TextInput::make('dimensions')
                    ->label('الأبعاد')
                    ->placeholder('مثال: 240×120 سم')
                    ->maxLength(100),
                Forms\Components\Select::make('unit')
                    ->label('وحدة القياس')
                    ->options([
                        'meter' => 'متر',
                        'board' => 'لوح',
                        'sqm' => 'متر مربع (م²)',
                        'ton' => 'طن',
                        'piece' => 'قطعة',
                        'kg' => 'كيلوغرام',
                    ])
                    ->required()
                    ->default('piece'),
            ])->columns(3),

            Forms\Components\Section::make('الأسعار والمخزون')->schema([
                Forms\Components\TextInput::make('default_price')
                    ->label('سعر البيع الافتراضي (جنيه)')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('ج.م')
                    ->required(),
                Forms\Components\TextInput::make('min_stock')
                    ->label('الحد الأدنى للمخزون')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ])->columns(3),

            Forms\Components\Section::make('ملاحظات')->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),
            ]),

            Forms\Components\Section::make('المخزون الافتتاحي')->schema([
                Forms\Components\Repeater::make('branchStock')
                    ->relationship()
                    ->label('رصيد الفروع')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('الفرع')
                            ->relationship('branch', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->distinct(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2)
                    ->addActionLabel('إضافة فرع')
                    ->defaultItems(0),
            ])->collapsible()->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الصنف')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit_label')
                    ->label('الوحدة'),
                Tables\Columns\TextColumn::make('default_price')
                    ->label('سعر البيع')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('إجمالي المخزون')
                    ->getStateUsing(fn (Product $record) => $record->total_stock)
                    ->badge()
                    ->color(fn (Product $record) => $record->isLowStock() ? 'danger' : 'success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('مخزون منخفض')
                    ->query(fn (Builder $query) => $query->whereHas('branchStock', function ($q) {
                        $q->whereColumn('quantity', '<=', 'products.min_stock');
                    })),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
