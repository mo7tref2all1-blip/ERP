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
        $total = 0;
        foreach ($data['items'] ?? [] as $item) {
            if (is_array($item)) {
                $total += floatval($item['total_egp'] ?? 0);
            }
        }
        $data['total_amount'] = $total;
        if ($data['total_amount'] <= 0) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('لا يمكن حفظ فاتورة بإجمالي صفر')
                ->send();
            $this->halt();
        }
        return $data;
    }
    protected function afterCreate(): void {
        app(InventoryService::class)->addStockFromPurchase($this->record);
    }
}
