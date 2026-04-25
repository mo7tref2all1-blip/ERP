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
        $total = $this->record->items()->sum('total_egp');
        if ($total <= 0) {
            $this->record->delete();
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('لا يمكن حفظ فاتورة بإجمالي صفر')
                ->send();
            $this->redirect($this->getResource()::getUrl('create'));
            return;
        }
        $this->record->update(['total_amount' => $total]);
        app(\App\Services\InventoryService::class)->addStockFromPurchase($this->record);
    }
}
