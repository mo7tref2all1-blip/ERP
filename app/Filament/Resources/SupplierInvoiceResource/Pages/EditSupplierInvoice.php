<?php
namespace App\Filament\Resources\SupplierInvoiceResource\Pages;
use App\Filament\Resources\SupplierInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSupplierInvoice extends EditRecord {
    protected static string $resource = SupplierInvoiceResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()->label('حذف')]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function mutateFormDataBeforeSave(array $data): array {
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
}
