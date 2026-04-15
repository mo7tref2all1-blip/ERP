<?php

namespace App\Console\Commands;

use App\Models\BranchStock;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckLowStockCommand extends Command
{
    protected $signature = 'inventory:check-low-stock';
    protected $description = 'التحقق من المخزون المنخفض وإرسال إشعارات';

    public function handle(): void
    {
        $lowStockItems = BranchStock::with(['product', 'branch'])
            ->join('products', 'branch_stock.product_id', '=', 'products.id')
            ->whereColumn('branch_stock.quantity', '<=', 'products.min_stock')
            ->where('products.is_active', true)
            ->where('products.min_stock', '>', 0)
            ->select('branch_stock.*')
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->info('لا توجد أصناف بمخزون منخفض.');
            return;
        }

        $admins = User::role(['super_admin', 'warehouse'])->get();

        foreach ($lowStockItems as $item) {
            Notification::send($admins, new LowStockNotification(
                $item->product,
                $item->branch,
                $item->quantity
            ));
        }

        $this->info("تم إرسال {$lowStockItems->count()} إشعار مخزون منخفض.");
    }
}
