<?php
namespace App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource;
use App\Models\ProductPrice;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditProduct extends EditRecord {
    protected static string $resource = ProductResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()->label('حذف')]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function afterSave(): void {
        // تسجيل تغيير السعر إن وُجد
        $originalPrice = $this->record->getOriginal('default_price');
        if ($originalPrice && $originalPrice != $this->record->default_price) {
            ProductPrice::create([
                'product_id' => $this->record->id,
                'price' => $this->record->default_price,
                'valid_from' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);
        }
    }
}
