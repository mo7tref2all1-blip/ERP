<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountsOverviewWidget extends BaseWidget
{
    protected ?string $heading = 'أرصدة الحسابات والخزائن';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        $accounts = BankAccount::where('is_active', true)->get();

        return $accounts->map(function (BankAccount $account) {
            $balance = $account->current_balance;
            return Stat::make($account->name, number_format($balance, 2) . ' ج.م')
                ->description($account->type_label)
                ->color($balance >= 0 ? 'success' : 'danger')
                ->icon($account->type === 'bank' ? 'heroicon-o-building-library' : 'heroicon-o-banknotes');
        })->toArray();
    }
}
