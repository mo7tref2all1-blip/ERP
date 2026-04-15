<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'الحسابات والمالية';
    protected static ?string $navigationLabel = 'الحسابات البنكية والخزائن';
    protected static ?string $modelLabel = 'حساب';
    protected static ?string $pluralModelLabel = 'الحسابات والخزائن';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الحساب')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الحساب')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('مثال: البنك الأهلي - حساب الشركة'),
                Forms\Components\Select::make('type')
                    ->label('نوع الحساب')
                    ->options(['bank' => 'حساب بنكي', 'cash' => 'خزينة نقدية'])
                    ->required()
                    ->default('cash'),
                Forms\Components\TextInput::make('bank_name')
                    ->label('اسم البنك')
                    ->maxLength(100),
                Forms\Components\TextInput::make('account_number')
                    ->label('رقم الحساب')
                    ->maxLength(100),
                Forms\Components\TextInput::make('opening_balance')
                    ->label('الرصيد الافتتاحي')
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م'),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ])->columns(2),
            Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الحساب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'bank' ? 'بنك' : 'خزينة')
                    ->color(fn ($state) => match($state) {
                        'bank' => 'info',
                        'cash' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('البنك'),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->label('الرصيد الافتتاحي')
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->getStateUsing(fn (BankAccount $record) => $record->current_balance)
                    ->money('EGP')
                    ->color(fn (BankAccount $record) => $record->current_balance >= 0 ? 'success' : 'danger')
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('transactions')
                    ->label('الحركات')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (BankAccount $record) => route('filament.admin.resources.account-transactions.index', ['tableFilters[bank_account_id][value]' => $record->id])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
