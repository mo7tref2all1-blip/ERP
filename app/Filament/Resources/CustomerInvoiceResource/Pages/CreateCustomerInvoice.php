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
        $data['total_amount'] = collect($data['items'] ?? [])->sum('total');
        $data['net_amount'] = $data['total_amount'] - floatval($data['discount_amount'] ?? 0);
        return $data;
    }
    protected function afterCreate(): void {
        // خصم المخزون وحساب FIFO تلقائياً
        app(InventoryService::class)->deductStockFromSale($this->record);
    }
}
