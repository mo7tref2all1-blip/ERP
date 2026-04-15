<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierInvoiceResource\Pages;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\InventoryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierInvoiceResource extends Resource
{
    protected static ?string $model = SupplierInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationGroup = 'المشتريات والموردون';
    protected static ?string $navigationLabel = 'فواتير الشراء';
    protected static ?string $modelLabel = 'فاتورة شراء';
    protected static ?string $pluralModelLabel = 'فواتير الشراء';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الفاتورة')->schema([
                Forms\Components\Select::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->label('اسم المورد')->required(),
                        Forms\Components\TextInput::make('phone')->label('الهاتف'),
                    ]),
                Forms\Components\TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'PUR-' . date('Y') . '-' . str_pad(SupplierInvoice::whereYear('created_at', date('Y'))->count() + 1, 4, '0', STR_PAD_LEFT)),
                Forms\Components\DatePicker::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->required()
                    ->default(today()),
                Forms\Components\DatePicker::make('due_date')
                    ->label('تاريخ الاستحقاق'),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع المستلم')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(3),

            Forms\Components\Section::make('أصناف الفاتورة')->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->label('الأصناف')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('الصنف')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateItemTotal($get, $set)),
                        Forms\Components\Radio::make('price_currency')
                            ->label('عملة السعر')
                            ->options(['egp' => 'جنيه مصري', 'usd' => 'دولار أمريكي'])
                            ->default('egp')
                            ->inline()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('cost_usd')
                            ->label('سعر الوحدة (دولار)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->visible(fn (Get $get) => $get('price_currency') === 'usd')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateFromDollar($get, $set)),
                        Forms\Components\TextInput::make('dollar_rate')
                            ->label('سعر الدولار (جنيه)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('ج.م')
                            ->visible(fn (Get $get) => $get('price_currency') === 'usd')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateFromDollar($get, $set)),
                        Forms\Components\TextInput::make('cost_egp')
                            ->label('سعر الوحدة (جنيه)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('ج.م')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateItemTotal($get, $set)),
                        Forms\Components\TextInput::make('total_egp')
                            ->label('الإجمالي (جنيه)')
                            ->numeric()
                            ->readOnly()
                            ->prefix('ج.م'),
                        Forms\Components\TextInput::make('notes')
                            ->label('ملاحظات')
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->addActionLabel('إضافة صنف')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::updateInvoiceTotal($get, $set))
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('الإجمالي')->schema([
                Forms\Components\TextInput::make('total_amount')
                    ->label('إجمالي الفاتورة (جنيه)')
                    ->numeric()
                    ->readOnly()
                    ->prefix('ج.م'),
            ]),
        ]);
    }

    protected static function calculateFromDollar(Get $get, Set $set): void
    {
        $usd = floatval($get('cost_usd') ?? 0);
        $rate = floatval($get('dollar_rate') ?? 0);
        if ($usd > 0 && $rate > 0) {
            $set('cost_egp', round($usd * $rate, 4));
            static::calculateItemTotal($get, $set);
        }
    }

    protected static function calculateItemTotal(Get $get, Set $set): void
    {
        $qty = floatval($get('quantity') ?? 0);
        $cost = floatval($get('cost_egp') ?? 0);
        $set('total_egp', round($qty * $cost, 2));
    }

    protected static function updateInvoiceTotal(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $total = collect($items)->sum(fn ($item) => floatval($item['total_egp'] ?? 0));
        $set('total_amount', round($total, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->getStateUsing(fn (SupplierInvoice $record) => $record->remaining_amount)
                    ->money('EGP')
                    ->color(fn (SupplierInvoice $record) => $record->remaining_amount > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'unpaid' => 'غير مدفوعة',
                        'partial' => 'جزئي',
                        'paid' => 'مدفوعة',
                        default => $state,
                    })
                    ->color(fn (string $state) => match($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(['unpaid' => 'غير مدفوعة', 'partial' => 'جزئي', 'paid' => 'مدفوعة']),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name'),
                Tables\Filters\Filter::make('invoice_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('to')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn ($q) => $q->whereDate('invoice_date', '>=', $data['from']))
                        ->when($data['to'], fn ($q) => $q->whereDate('invoice_date', '<=', $data['to']))
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('add_payment')
                    ->label('تسجيل دفعة')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (SupplierInvoice $record) => $record->status !== 'paid')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('مبلغ الدفعة')
                            ->numeric()
                            ->required()
                            ->prefix('ج.م'),
                        Forms\Components\Select::make('bank_account_id')
                            ->label('من حساب')
                            ->options(BankAccount::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الدفع')
                            ->required()
                            ->default(today()),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('رقم المرجع'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ])
                    ->action(function (SupplierInvoice $record, array $data) {
                        $record->payments()->create([
                            ...$data,
                            'supplier_id' => $record->supplier_id,
                            'created_by' => auth()->id(),
                        ]);
                        Notification::make()->success()->title('تم تسجيل الدفعة بنجاح')->send();
                    }),
                Tables\Actions\Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->url(fn (SupplierInvoice $record) => route('supplier-invoice.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('invoice_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierInvoices::route('/'),
            'create' => Pages\CreateSupplierInvoice::route('/create'),
            'edit' => Pages\EditSupplierInvoice::route('/{record}/edit'),
        ];
    }
}
