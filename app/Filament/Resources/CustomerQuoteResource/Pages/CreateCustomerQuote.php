<?php
namespace App\Filament\Resources\CustomerQuoteResource\Pages;
use App\Filament\Resources\CustomerQuoteResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCustomerQuote extends CreateRecord {
    protected static string $resource = CustomerQuoteResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
