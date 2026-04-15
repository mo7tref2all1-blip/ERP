<?php
namespace App\Filament\Resources\CustomerQuoteResource\Pages;
use App\Filament\Resources\CustomerQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCustomerQuote extends EditRecord {
    protected static string $resource = CustomerQuoteResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()->label('حذف')]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
