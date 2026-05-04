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
                    ->default(function () {
                        $year = date('Y');
                        $count = SupplierInvoice::whereYear('created_at', $year)->count() + 1;
                        $number = 'PUR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
                        while (SupplierInvoice::where('invoice_number', $number)->exists()) {
                            $count++;
                            $number = 'PUR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
                        }
                        return $number;
                    }),
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

                        // Wood section
                        Forms\Components\Fieldset::make('بيانات الخشب')->schema([
                            Forms\Components\TextInput::make('m3_quantity')
                                ->label('الكمية م³ (X)')
                                ->numeric()
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateWoodCosts($get, $set)),
                            Forms\Components\TextInput::make('usd_price_per_m3')
                                ->label('سعر م³ بالدولار (Y)')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateWoodCosts($get, $set)),
                            Forms\Components\TextInput::make('dollar_rate')
                                ->label('سعر الدولار ج.م (R1)')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ج.م')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateWoodCosts($get, $set)),
                            Forms\Components\TextInput::make('boards_per_m3')
                                ->label('عدد الألواح/م³ (Z)')
                                ->numeric()
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateWoodCosts($get, $set)),
                        ])->columns(4)->columnSpanFull(),

                        // Customs section
                        Forms\Components\Fieldset::make('بيانات الجمارك')->schema([
                            Forms\Components\TextInput::make('customs_usd_price')
                                ->label('سعر الجمارك بالدولار (Y2)')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateWoodCosts($get, $set)),
                            Forms\Components\TextInput::make('customs_exchange_rate')
                                ->label('سعر صرف الجمارك (R2)')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ج.م')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateWoodCosts($get, $set)),
                            Forms\Components\TextInput::make('customs_percentage')
                                ->label('نسبة الجمارك % (C%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateWoodCosts($get, $set)),
                        ])->columns(3)->columnSpanFull(),

                        // Computed results
                        Forms\Components\Fieldset::make('النتائج المحسوبة')->schema([
                            Forms\Components\TextInput::make('total_boards')
                                ->label('إجمالي الألواح')
                                ->numeric()
                                ->readOnly()
                                ->prefix('لوح'),
                            Forms\Components\TextInput::make('customs_amount')
                                ->label('قيمة الجمارك (ج.م)')
                                ->numeric()
                                ->readOnly()
                                ->prefix('ج.م'),
                            Forms\Components\TextInput::make('cost_per_board')
                                ->label('تكلفة اللوح (ج.م)')
                                ->numeric()
                                ->readOnly()
                                ->prefix('ج.م'),
                            Forms\Components\TextInput::make('total_egp')
                                ->label('إجمالي الصنف (ج.م)')
                                ->numeric()
                                ->readOnly()
                                ->prefix('ج.م'),
                        ])->columns(4)->columnSpanFull(),

                        // Hidden fields populated by calculation
                        Forms\Components\Hidden::make('quantity'),
                        Forms\Components\Hidden::make('cost_egp'),
                        Forms\Components\Hidden::make('cost_usd'),

                        Forms\Components\TextInput::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
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
        $items = $get('../../items') ?? [];
        $total = collect($items)->sum(fn ($item) => floatval($item['total_egp'] ?? 0));
        $set('../../total_amount', round($total, 2));
    }

    protected static function calculateWoodCosts(Get $get, Set $set): void
    {
        $x = floatval($get('m3_quantity') ?? 0);        // M3 quantity
        $y = floatval($get('usd_price_per_m3') ?? 0);   // USD/M3
        $r1 = floatval($get('dollar_rate') ?? 0);        // EGP per USD (existing field)
        $z = floatval($get('boards_per_m3') ?? 0);       // boards per M3
        $y2 = floatval($get('customs_usd_price') ?? 0);  // customs USD price/M3
        $r2 = floatval($get('customs_exchange_rate') ?? 0); // customs exchange rate
        $c = floatval($get('customs_percentage') ?? 0);  // customs %

        $totalBoards = $x * $z;
        $totalUsd = $x * $y;
        $totalEgp = $totalUsd * $r1;

        $customsBaseUsd = $x * $y2;
        $customsBaseEgp = $customsBaseUsd * $r2;
        $customsAmount = $customsBaseEgp * ($c / 100);

        $costPerBoard = ($totalBoards > 0) ? ($totalEgp + $customsAmount) / $totalBoards : 0;

        $set('total_boards', round($totalBoards, 2));
        $set('customs_amount', round($customsAmount, 2));
        $set('cost_per_board', round($costPerBoard, 2));
        $set('total_egp', round($totalEgp, 2));

        // Populate hidden fields used by inventory service
        $set('quantity', round($totalBoards, 3));
        $set('cost_egp', round($costPerBoard, 4));
        $set('cost_usd', round($y, 4));

        $items = $get('../../items') ?? [];
        $invoiceTotal = collect($items)->sum(fn ($item) => floatval($item['total_egp'] ?? 0));
        $set('../../total_amount', round($invoiceTotal, 2));
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
                        $payment = $record->payments()->create([
                            ...$data,
                            'supplier_id' => $record->supplier_id,
                            'created_by' => auth()->id(),
                        ]);
                        \App\Models\AccountTransaction::create([
                            'bank_account_id' => $data['bank_account_id'],
                            'type' => 'out',
                            'amount' => $data['amount'],
                            'transaction_date' => $data['payment_date'],
                            'reference_type' => \App\Models\SupplierPayment::class,
                            'reference_id' => $payment->id,
                            'description' => 'دفعة لمورد - فاتورة: ' . $record->invoice_number,
                            'created_by' => auth()->id(),
                        ]);
                        $record->updatePaymentStatus();
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
