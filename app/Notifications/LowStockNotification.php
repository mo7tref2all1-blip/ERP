<?php

namespace App\Notifications;

use App\Models\Branch;
use App\Models\Product;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Product $product,
        private Branch $branch,
        private float $currentQuantity,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->warning()
            ->title('تحذير: مخزون منخفض')
            ->body("الصنف [{$this->product->name}] في فرع [{$this->branch->name}] وصل للحد الأدنى. الكمية الحالية: {$this->currentQuantity} {$this->product->unit_label}")
            ->icon('heroicon-o-exclamation-triangle')
            ->getDatabaseMessage();
    }
}
