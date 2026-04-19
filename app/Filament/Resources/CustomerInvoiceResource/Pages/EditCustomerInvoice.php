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
        $data['total_amount'] = collect($data['items'] ?? [])->sum('total');
        $data['net_amount'] = $data['total_amount'] - floatval($data['discount_amount'] ?? 0);
        return $data;
    }
}
