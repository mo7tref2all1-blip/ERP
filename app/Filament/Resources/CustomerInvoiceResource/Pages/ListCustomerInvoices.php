<?php
namespace App\Filament\Resources\CustomerInvoiceResource\Pages;
use App\Filament\Resources\CustomerInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCustomerInvoices extends ListRecords {
    protected static string $resource = CustomerInvoiceResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('فاتورة بيع جديدة')]; }
}
