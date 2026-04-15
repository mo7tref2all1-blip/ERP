<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $navigationLabel = 'المستخدمون';
    protected static ?string $modelLabel = 'مستخدم';
    protected static ?string $pluralModelLabel = 'المستخدمون';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المستخدم')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel(),
                Forms\Components\TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->maxLength(255),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('roles')
                    ->label('الدور الوظيفي')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record) => match($record->name) {
                        'super_admin' => 'مدير النظام',
                        'accountant' => 'محاسب',
                        'warehouse' => 'أمين مخزن',
                        'salesperson' => 'بائع',
                        default => $record->name,
                    })
                    ->multiple()
                    ->preload(),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'super_admin' => 'مدير النظام',
                        'accountant' => 'محاسب',
                        'warehouse' => 'أمين مخزن',
                        'salesperson' => 'بائع',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
