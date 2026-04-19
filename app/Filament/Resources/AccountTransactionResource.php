<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountTransactionResource\Pages;
use App\Models\AccountTransaction;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountTransactionResource extends Resource
{
    protected static ?string $model = AccountTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'الحسابات والمالية';
    protected static ?string $navigationLabel = 'حركات الحسابات';
    protected static ?string $modelLabel = 'حركة حساب';
    protected static ?string $pluralModelLabel = 'حركات الحسابات';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->label('الحساب')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => $state === 'in' ? 'وارد' : 'صادر')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('البيان')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('وقت الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label('الحساب')
                    ->options(BankAccount::pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(['in' => 'وارد', 'out' => 'صادر']),
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('to')->label('إلى'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q) => $q->whereDate('transaction_date', '>=', $data['from']))
                        ->when($data['to'], fn ($q) => $q->whereDate('transaction_date', '<=', $data['to']))
                    ),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountTransactions::route('/'),
        ];
    }
}
