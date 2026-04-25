<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerInvoiceResource\Pages;
use App\Models\BankAccount;
use App\Models\CustomerInvoice;
use App\Models\Product;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerInvoiceResource extends Resource
{
    protected static ?string $model = CustomerInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'المبيعات';
    protected static ?string $navigationLabel = 'فواتير البيع';
    protected static ?string $modelLabel = 'فاتورة بيع';
    protected static ?string $pluralModelLabel = 'فواتير البيع';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $maxDiscountPercent = Setting::get('salesperson_max_discount', 10);

        return $form->schema([
            Forms\Components\Section::make('بيانات الفاتورة')->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->label('اسم العميل')->required(),
                        Forms\Components\TextInput::make('phone')->label('الهاتف'),
                        Forms\Components\Select::make('type')->label('النوع')
                            ->options(['individual' => 'فرد', 'company' => 'شركة'])->default('individual'),
                    ]),
                Forms\Components\TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(function () {
                        $year = date('Y');
                        $count = CustomerInvoice::whereYear('created_at', $year)->count() + 1;
                        $number = 'INV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
                        while (CustomerInvoice::where('invoice_number', $number)->exists()) {
                            $count++;
                            $number = 'INV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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
                    ->label('الفرع')
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
                            ->options(Product::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('default_price', $product->default_price);
                                        $set('unit_price', $product->default_price);
                                        self::calculateLineTotal($get, $set);
                                    }
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateLineTotal($get, $set)),
                        Forms\Components\TextInput::make('default_price')
                            ->label('السعر الافتراضي')
                            ->numeric()
                            ->readOnly()
                            ->prefix('ج.م'),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('سعر البيع الفعلي')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateLineTotal($get, $set)),
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('خصم %')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(auth()->user()?->hasRole('salesperson') ? $maxDiscountPercent : 100)
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateLineTotal($get, $set)),
                        Forms\Components\TextInput::make('total')
                            ->label('الإجمالي')
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
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('الإجماليات')->schema([
                Forms\Components\TextInput::make('total_amount')
                    ->label('إجمالي الأصناف')
                    ->numeric()
                    ->readOnly()
                    ->prefix('ج.م'),
                Forms\Components\TextInput::make('discount_amount')
                    ->label('إجمالي الخصم')
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('net_amount', floatval($get('total_amount')) - floatval($get('discount_amount')))),
                Forms\Components\TextInput::make('net_amount')
                    ->label('صافي الفاتورة')
                    ->numeric()
                    ->readOnly()
                    ->prefix('ج.م'),
            ])->columns(3),
        ]);
    }

    protected static function calculateLineTotal(Get $get, Set $set): void
    {
        $qty = floatval($get('quantity') ?? 0);
        $price = floatval($get('unit_price') ?? 0);
        $discount = floatval($get('discount_percent') ?? 0);
        $total = $qty * $price * (1 - $discount / 100);
        $set('total', round($total, 2));
        $items = $get('../../items') ?? [];
        $invoiceTotal = collect($items)->sum(fn ($item) => floatval($item['total'] ?? 0));
        $set('../../total_amount', round($invoiceTotal, 2));
        $discountAmount = floatval($get('../../discount_amount') ?? 0);
        $set('../../net_amount', round($invoiceTotal - $discountAmount, 2));
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $total = collect($items)->sum(fn ($item) => floatval($item['total'] ?? 0));
        $set('total_amount', round($total, 2));
        $discount = floatval($get('discount_amount') ?? 0);
        $set('net_amount', round($total - $discount, 2));
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
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('صافي الفاتورة')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->getStateUsing(fn (CustomerInvoice $record) => $record->remaining_amount)
                    ->money('EGP')
                    ->color(fn (CustomerInvoice $record) => $record->remaining_amount > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'unpaid' => 'غير مدفوعة',
                        'partial' => 'جزئي',
                        'paid' => 'مدفوعة',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(['unpaid' => 'غير مدفوعة', 'partial' => 'جزئي', 'paid' => 'مدفوعة']),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('add_payment')
                    ->label('تسجيل دفعة')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (CustomerInvoice $record) => $record->status !== 'paid')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('مبلغ الدفعة')
                            ->numeric()
                            ->required()
                            ->prefix('ج.م'),
                        Forms\Components\Select::make('bank_account_id')
                            ->label('في حساب')
                            ->options(BankAccount::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الدفع')
                            ->required()
                            ->default(today()),
                        Forms\Components\TextInput::make('reference_number')->label('رقم المرجع'),
                        Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(2),
                    ])
                    ->action(function (CustomerInvoice $record, array $data) {
                        $payment = $record->payments()->create([
                            ...$data,
                            'customer_id' => $record->customer_id,
                            'created_by' => auth()->id(),
                        ]);
                        \App\Models\AccountTransaction::create([
                            'bank_account_id' => $data['bank_account_id'],
                            'type' => 'in',
                            'amount' => $data['amount'],
                            'transaction_date' => $data['payment_date'],
                            'reference_type' => \App\Models\CustomerPayment::class,
                            'reference_id' => $payment->id,
                            'description' => 'دفعة من عميل - فاتورة: ' . $record->invoice_number,
                            'created_by' => auth()->id(),
                        ]);
                        $record->updatePaymentStatus();
                        Notification::make()->success()->title('تم تسجيل الدفعة بنجاح')->send();
                    }),
                Tables\Actions\Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->url(fn (CustomerInvoice $record) => route('customer-invoice.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('invoice_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerInvoices::route('/'),
            'create' => Pages\CreateCustomerInvoice::route('/create'),
            'edit' => Pages\EditCustomerInvoice::route('/{record}/edit'),
        ];
    }
}
