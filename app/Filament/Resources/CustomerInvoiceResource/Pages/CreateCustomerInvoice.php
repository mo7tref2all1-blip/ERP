<?php
namespace App\Filament\Resources\CustomerInvoiceResource\Pages;
use App\Filament\Resources\CustomerInvoiceResource;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
class CreateCustomerInvoice extends CreateRecord {
    protected static string $resource = CustomerInvoiceResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['created_by'] = auth()->id();
        return $data;
    }
    protected function afterCreate(): void {
        // خصم المخزون وحساب FIFO تلقائياً
        app(InventoryService::class)->deductStockFromSale($this->record);
    }
}
