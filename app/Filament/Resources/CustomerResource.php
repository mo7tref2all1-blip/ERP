<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'المبيعات';
    protected static ?string $navigationLabel = 'العملاء';
    protected static ?string $modelLabel = 'عميل';
    protected static ?string $pluralModelLabel = 'العملاء';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات العميل')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم العميل')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\Select::make('type')
                    ->label('نوع العميل')
                    ->options(['individual' => 'فرد', 'company' => 'شركة'])
                    ->required()
                    ->default('individual'),
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rows(2),
                Forms\Components\TextInput::make('tax_number')
                    ->label('الرقم الضريبي')
                    ->maxLength(50),
                Forms\Components\TextInput::make('opening_balance')
                    ->label('الرصيد الافتتاحي (جنيه)')
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م'),
            ])->columns(2),
            Forms\Components\Section::make('ملاحظات')->schema([
                Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(3),
                Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => $state === 'company' ? 'شركة' : 'فرد')
                    ->colors(['info' => 'company', 'gray' => 'individual']),
                Tables\Columns\TextColumn::make('total_invoices')
                    ->label('إجمالي الفواتير')
                    ->getStateUsing(fn (Customer $record) => number_format($record->total_invoices, 2) . ' ج.م')
                    ->color('warning'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('الرصيد المستحق')
                    ->getStateUsing(fn (Customer $record) => number_format($record->balance, 2) . ' ج.م')
                    ->color(fn (Customer $record) => $record->balance > 0 ? 'danger' : 'success')
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(['individual' => 'فرد', 'company' => 'شركة']),
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
                Tables\Filters\Filter::make('has_balance')
                    ->label('عملاء بديون')
                    ->query(fn ($query) => $query->whereHas('invoices', fn ($q) => $q->where('status', '!=', 'paid'))),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('invoices')
                    ->label('الفواتير')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Customer $record) => CustomerInvoiceResource::getUrl('index', ['tableFilters[customer_id][value]' => $record->id])),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
