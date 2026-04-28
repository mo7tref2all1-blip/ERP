<?php
namespace App\Filament\Pages;
use Filament\Pages\Page;
class SystemUpdate extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $navigationLabel = 'تحديث النظام';
    protected static string $view = 'filament.pages.system-update';
    protected static ?int $navigationSort = 99;
    public static function canAccess(): bool {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
