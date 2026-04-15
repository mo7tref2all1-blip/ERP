<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'المشتريات والموردون';
    protected static ?string $navigationLabel = 'الموردون';
    protected static ?string $modelLabel = 'مورد';
    protected static ?string $pluralModelLabel = 'الموردون';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المورد')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المورد')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rows(2),
                Forms\Components\TextInput::make('bank_account')
                    ->label('الحساب البنكي')
                    ->maxLength(255),
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
                    ->label('اسم المورد')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_invoices')
                    ->label('إجمالي الفواتير')
                    ->getStateUsing(fn (Supplier $record) => number_format($record->total_invoices, 2) . ' ج.م')
                    ->color('warning'),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('إجمالي المدفوع')
                    ->getStateUsing(fn (Supplier $record) => number_format($record->total_paid, 2) . ' ج.م')
                    ->color('success'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('المتبقي')
                    ->getStateUsing(fn (Supplier $record) => number_format($record->balance, 2) . ' ج.م')
                    ->color(fn (Supplier $record) => $record->balance > 0 ? 'danger' : 'success')
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('invoices')
                    ->label('الفواتير')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Supplier $record) => SupplierInvoiceResource::getUrl('index', ['tableFilters[supplier_id][value]' => $record->id])),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
