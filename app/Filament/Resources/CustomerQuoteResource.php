<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerQuoteResource\Pages;
use App\Models\CustomerQuote;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerQuoteResource extends Resource
{
    protected static ?string $model = CustomerQuote::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'المبيعات';
    protected static ?string $navigationLabel = 'عروض الأسعار';
    protected static ?string $modelLabel = 'عرض سعر';
    protected static ?string $pluralModelLabel = 'عروض الأسعار';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات عرض السعر')->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('quote_number')
                    ->label('رقم العرض')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'QTE-' . date('Y') . '-' . str_pad(CustomerQuote::whereYear('created_at', date('Y'))->count() + 1, 4, '0', STR_PAD_LEFT)),
                Forms\Components\DatePicker::make('quote_date')
                    ->label('تاريخ العرض')
                    ->required()
                    ->default(today()),
                Forms\Components\DatePicker::make('valid_until')
                    ->label('صالح حتى')
                    ->default(today()->addDays(30)),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'sent' => 'مُرسل',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ])
                    ->default('draft'),
            ])->columns(3),

            Forms\Components\Section::make('الأصناف')->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->label('الأصناف')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('الصنف')
                            ->options(Product::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('default_price', $product->default_price);
                                        $set('unit_price', $product->default_price);
                                    }
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->reactive()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calcTotal($get, $set)),
                        Forms\Components\TextInput::make('default_price')
                            ->label('السعر الافتراضي')
                            ->numeric()
                            ->readOnly()
                            ->prefix('ج.م'),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('سعر البيع')
                            ->numeric()
                            ->required()
                            ->prefix('ج.م')
                            ->reactive()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calcTotal($get, $set)),
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('خصم %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->reactive()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calcTotal($get, $set)),
                        Forms\Components\TextInput::make('total')
                            ->label('الإجمالي')
                            ->numeric()
                            ->readOnly()
                            ->prefix('ج.م'),
                    ])
                    ->columns(4)
                    ->addActionLabel('إضافة صنف')
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('الإجمالي')->schema([
                Forms\Components\TextInput::make('total_amount')
                    ->label('الإجمالي')
                    ->numeric()
                    ->readOnly()
                    ->prefix('ج.م'),
                Forms\Components\TextInput::make('discount_amount')
                    ->label('خصم إجمالي')
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م')
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('net_amount', floatval($get('total_amount')) - floatval($get('discount_amount')))),
                Forms\Components\TextInput::make('net_amount')
                    ->label('الصافي')
                    ->numeric()
                    ->readOnly()
                    ->prefix('ج.م'),
                Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    protected static function calcTotal(Get $get, Set $set): void
    {
        $qty = floatval($get('quantity') ?? 0);
        $price = floatval($get('unit_price') ?? 0);
        $discount = floatval($get('discount_percent') ?? 0);
        $set('total', round($qty * $price * (1 - $discount / 100), 2));
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $total = collect($items)->sum(fn ($item) => floatval($item['total'] ?? 0));
        $set('total_amount', round($total, 2));
        $set('net_amount', round($total - floatval($get('discount_amount') ?? 0), 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')->label('رقم العرض')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')->label('العميل')->searchable(),
                Tables\Columns\TextColumn::make('quote_date')->label('التاريخ')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('valid_until')->label('صالح حتى')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('net_amount')->label('الإجمالي')->money('EGP'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'مسودة', 'sent' => 'مُرسل',
                        'accepted' => 'مقبول', 'rejected' => 'مرفوض', 'expired' => 'منتهي',
                        default => $state,
                    })
                    ->colors(['gray' => 'draft', 'info' => 'sent', 'success' => 'accepted', 'danger' => 'rejected', 'warning' => 'expired']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(['draft' => 'مسودة', 'sent' => 'مُرسل', 'accepted' => 'مقبول', 'rejected' => 'مرفوض']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('convert')
                    ->label('تحويل لفاتورة')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (CustomerQuote $record) => $record->status === 'accepted')
                    ->requiresConfirmation()
                    ->action(function (CustomerQuote $record) {
                        $invoice = $record->convertToInvoice();
                        Notification::make()->success()->title('تم إنشاء فاتورة البيع بنجاح: ' . $invoice->invoice_number)->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('quote_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerQuotes::route('/'),
            'create' => Pages\CreateCustomerQuote::route('/create'),
            'edit' => Pages\EditCustomerQuote::route('/{record}/edit'),
        ];
    }
}
