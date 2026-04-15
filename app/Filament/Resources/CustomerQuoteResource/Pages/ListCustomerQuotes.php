<?php
namespace App\Filament\Resources\CustomerQuoteResource\Pages;
use App\Filament\Resources\CustomerQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCustomerQuotes extends ListRecords {
    protected static string $resource = CustomerQuoteResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('إنشاء عرض سعر')]; }
}
