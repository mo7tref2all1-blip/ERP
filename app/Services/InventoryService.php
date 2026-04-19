<?php

namespace App\Services;

use App\Models\BranchStock;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\SupplierInvoice;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class InventoryService
{
    /**
     * إضافة مخزون عند استلام فاتورة شراء
     */
    public function addStockFromPurchase(SupplierInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                $this->addStock(
                    productId: $item->product_id,
                    branchId: $invoice->branch_id,
                    quantity: $item->quantity,
                    costEgp: $item->cost_egp,
                    type: 'purchase',
                    referenceType: SupplierInvoice::class,
                    referenceId: $invoice->id,
                    supplierInvoiceItemId: $item->id,
                    costUsd: $item->cost_usd,
                    dollarRate: $item->dollar_rate,
                );
            }
        });
    }

    /**
     * خصم المخزون عند إنشاء فاتورة بيع مع حساب FIFO
     */
    public function deductStockFromSale(CustomerInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                $fifoCost = $this->deductStockFIFO(
                    productId: $item->product_id,
                    branchId: $invoice->branch_id,
                    quantity: $item->quantity,
                    referenceType: CustomerInvoice::class,
                    referenceId: $invoice->id,
                );

                // تحديث التكلفة والربح في سطر الفاتورة
                $item->update([
                    'cost_egp_fifo' => $fifoCost,
                    'profit' => $item->total - ($fifoCost * $item->quantity),
                ]);
            }

            // تحديث إجمالي التكلفة والربح في الفاتورة
            $totalCost = $invoice->items()->sum(DB::raw('cost_egp_fifo * quantity'));
            $invoice->update([
                'total_cost_fifo' => $totalCost,
                'profit_amount' => $invoice->net_amount - $totalCost,
            ]);
        });
    }

    /**
     * إضافة مخزون لفرع معين
     */
    public function addStock(
        int $productId,
        int $branchId,
        float $quantity,
        float $costEgp,
        string $type,
        string $referenceType = null,
        int $referenceId = null,
        int $supplierInvoiceItemId = null,
        float $costUsd = null,
        float $dollarRate = null,
        string $notes = null,
    ): void {
        $stock = BranchStock::firstOrCreate(
            ['product_id' => $productId, 'branch_id' => $branchId],
            ['quantity' => 0]
        );

        $quantityBefore = $stock->quantity;
        $stock->increment('quantity', $quantity);

        // تسجيل حركة المخزون
        StockMovement::create([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore + $quantity,
            'unit_cost' => $costEgp,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => auth()->id() ?? 1,
            'notes' => $notes,
        ]);

        // إنشاء دفعة FIFO
        StockBatch::create([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'supplier_invoice_item_id' => $supplierInvoiceItemId,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'cost_egp' => $costEgp,
            'cost_usd' => $costUsd,
            'dollar_rate' => $dollarRate,
            'batch_date' => now()->toDateString(),
        ]);
    }

    /**
     * خصم مخزون بطريقة FIFO - يرجع متوسط تكلفة الوحدة
     */
    public function deductStockFIFO(
        int $productId,
        int $branchId,
        float $quantity,
        string $referenceType = null,
        int $referenceId = null,
        string $notes = null,
    ): float {
        $stock = BranchStock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$stock || $stock->quantity < $quantity) {
            throw new \Exception("الكمية المطلوبة ({$quantity}) أكبر من المخزون المتاح (" . ($stock ? $stock->quantity : 0) . ")");
        }

        $quantityBefore = $stock->quantity;
        $remainingToDeduct = $quantity;
        $totalCost = 0;

        // الحصول على الدفعات بترتيب FIFO (الأقدم أولاً)
        $batches = StockBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('batch_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $deductFromBatch = min($remainingToDeduct, $batch->remaining_quantity);
            $totalCost += $deductFromBatch * $batch->cost_egp;
            $batch->decrement('remaining_quantity', $deductFromBatch);
            $remainingToDeduct -= $deductFromBatch;
        }

        // تحديث المخزون
        $stock->decrement('quantity', $quantity);

        // تسجيل حركة المخزون
        StockMovement::create([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'type' => 'sale',
            'quantity' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore - $quantity,
            'unit_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => auth()->id() ?? 1,
            'notes' => $notes,
        ]);

        // التحقق من الحد الأدنى وإرسال إشعار
        $this->checkLowStock($productId, $branchId);

        return $quantity > 0 ? $totalCost / $quantity : 0; // متوسط تكلفة الوحدة
    }

    /**
     * تحويل مخزون بين الفروع
     */
    public function transferBetweenBranches(
        int $productId,
        int $fromBranchId,
        int $toBranchId,
        float $quantity,
        string $notes = null,
    ): void {
        DB::transaction(function () use ($productId, $fromBranchId, $toBranchId, $quantity, $notes) {
            $fromStock = BranchStock::where('product_id', $productId)
                ->where('branch_id', $fromBranchId)
                ->first();

            if (!$fromStock || $fromStock->quantity < $quantity) {
                throw new \Exception('الكمية المطلوبة أكبر من المخزون المتاح في الفرع المصدر');
            }

            // خصم من الفرع المصدر
            $fromBefore = $fromStock->quantity;
            $fromStock->decrement('quantity', $quantity);

            // إضافة للفرع المستهدف
            $toStock = BranchStock::firstOrCreate(
                ['product_id' => $productId, 'branch_id' => $toBranchId],
                ['quantity' => 0]
            );
            $toBefore = $toStock->quantity;
            $toStock->increment('quantity', $quantity);

            // تحديث دفعات FIFO (نقلها من فرع لآخر)
            $remainingToTransfer = $quantity;
            $batches = StockBatch::where('product_id', $productId)
                ->where('branch_id', $fromBranchId)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('batch_date')
                ->orderBy('id')
                ->get();

            foreach ($batches as $batch) {
                if ($remainingToTransfer <= 0) break;
                $transferFromBatch = min($remainingToTransfer, $batch->remaining_quantity);
                $batch->decrement('remaining_quantity', $transferFromBatch);

                // إنشاء دفعة جديدة في الفرع المستهدف بنفس التكلفة
                StockBatch::create([
                    'product_id' => $productId,
                    'branch_id' => $toBranchId,
                    'quantity' => $transferFromBatch,
                    'remaining_quantity' => $transferFromBatch,
                    'cost_egp' => $batch->cost_egp,
                    'cost_usd' => $batch->cost_usd,
                    'dollar_rate' => $batch->dollar_rate,
                    'batch_date' => $batch->batch_date,
                ]);

                $remainingToTransfer -= $transferFromBatch;
            }

            $userId = auth()->id() ?? 1;

            // تسجيل حركة صادر
            StockMovement::create([
                'product_id' => $productId,
                'branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'type' => 'transfer_out',
                'quantity' => -$quantity,
                'quantity_before' => $fromBefore,
                'quantity_after' => $fromBefore - $quantity,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            // تسجيل حركة وارد
            StockMovement::create([
                'product_id' => $productId,
                'branch_id' => $toBranchId,
                'to_branch_id' => $fromBranchId,
                'type' => 'transfer_in',
                'quantity' => $quantity,
                'quantity_before' => $toBefore,
                'quantity_after' => $toBefore + $quantity,
                'user_id' => $userId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * التحقق من الحد الأدنى للمخزون وإرسال إشعار
     */
    private function checkLowStock(int $productId, int $branchId): void
    {
        $stock = BranchStock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->with('product')
            ->first();

        if ($stock && $stock->product->isLowStock($branchId)) {
            // إشعار للمدراء وأمناء المخزن
            $admins = User::role(['super_admin', 'warehouse'])->get();
            Notification::send($admins, new LowStockNotification($stock->product, $stock->branch, $stock->quantity));
        }
    }
}
