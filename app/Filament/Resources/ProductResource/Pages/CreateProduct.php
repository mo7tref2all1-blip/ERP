<?php
namespace App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource;
use App\Models\ProductPrice;
use Filament\Resources\Pages\CreateRecord;
class CreateProduct extends CreateRecord {
    protected static string $resource = ProductResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function afterCreate(): void {
        // تسجيل أول سعر في سجل الأسعار
        ProductPrice::create([
            'product_id' => $this->record->id,
            'price' => $this->record->default_price,
            'valid_from' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);
    }
}
