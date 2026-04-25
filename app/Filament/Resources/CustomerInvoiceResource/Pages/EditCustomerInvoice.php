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
        return $data;
    }
    protected function afterSave(): void {
        $total = $this->record->items()->sum('total');
        $net = $total - floatval($this->record->discount_amount ?? 0);
        $this->record->update(['total_amount' => $total, 'net_amount' => $net]);
    }
}
