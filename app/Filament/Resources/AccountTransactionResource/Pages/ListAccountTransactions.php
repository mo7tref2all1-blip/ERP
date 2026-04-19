<?php

namespace App\Filament\Resources\AccountTransactionResource\Pages;

use App\Filament\Resources\AccountTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountTransactions extends ListRecords
{
    protected static string $resource = AccountTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
