<?php
namespace App\Filament\Resources\CustomerInvoiceResource\Pages;
use App\Filament\Resources\CustomerInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCustomerInvoice extends EditRecord {
    protected static string $resource = CustomerInvoiceResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()->label('حذف')]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
