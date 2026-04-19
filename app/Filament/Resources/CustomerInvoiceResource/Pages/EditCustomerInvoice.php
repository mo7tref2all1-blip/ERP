<?php
namespace App\Filament\Resources\CustomerInvoiceResource\Pages;
use App\Filament\Resources\CustomerInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCustomerInvoice extends EditRecord {
    protected static string $resource = CustomerInvoiceResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()->label('حذف')]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function mutateFormDataBeforeSave(array $data): array {
        $total = 0;
        foreach ($data['items'] ?? [] as $item) {
            if (is_array($item)) {
                $total += floatval($item['total'] ?? 0);
            }
        }
        $data['total_amount'] = $total;
        $data['net_amount'] = $total - floatval($data['discount_amount'] ?? 0);
        if ($data['net_amount'] <= 0) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('لا يمكن حفظ فاتورة بإجمالي صفر')
                ->send();
            $this->halt();
        }
        return $data;
    }
}
