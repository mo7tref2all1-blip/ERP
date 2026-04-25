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
        $total = $this->record->items()->sum('total');
        $net = $total - floatval($this->record->discount_amount ?? 0);
        if ($net <= 0) {
            $this->record->delete();
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('لا يمكن حفظ فاتورة بإجمالي صفر')
                ->send();
            $this->redirect($this->getResource()::getUrl('create'));
            return;
        }
        $this->record->update(['total_amount' => $total, 'net_amount' => $net]);
        app(\App\Services\InventoryService::class)->deductStockFromSale($this->record);
    }
}
