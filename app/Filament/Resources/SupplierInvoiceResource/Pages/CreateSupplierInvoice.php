<?php
namespace App\Filament\Resources\SupplierInvoiceResource\Pages;
use App\Filament\Resources\SupplierInvoiceResource;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
class CreateSupplierInvoice extends CreateRecord {
    protected static string $resource = SupplierInvoiceResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['created_by'] = auth()->id();
        return $data;
    }
    protected function afterCreate(): void {
        // إضافة المخزون تلقائياً عند حفظ الفاتورة
        app(InventoryService::class)->addStockFromPurchase($this->record);
    }
}
