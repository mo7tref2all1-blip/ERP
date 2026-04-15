<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'لوحة التحكم';
    protected static ?string $title = 'لوحة التحكم';

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
