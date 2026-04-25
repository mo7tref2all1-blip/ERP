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
        return $data;
    }
    protected function afterSave(): void {
        $total = $this->record->items()->sum('total_egp');
        $this->record->update(['total_amount' => $total]);
    }
}
