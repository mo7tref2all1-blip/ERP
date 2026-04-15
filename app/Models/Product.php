<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'code', 'type', 'thickness_mm',
        'dimensions', 'unit', 'default_price', 'min_stock', 'notes', 'is_active',
    ];

    protected $casts = [
        'thickness_mm' => 'decimal:2',
        'default_price' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function branchStock(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function supplierInvoiceItems(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    public function customerInvoiceItems(): HasMany
    {
        return $this->hasMany(CustomerInvoiceItem::class);
    }

    /**
     * الحصول على إجمالي الكمية في كل الفروع
     */
    public function getTotalStockAttribute(): float
    {
        return $this->branchStock()->sum('quantity');
    }

    /**
     * الحصول على الكمية في فرع معين
     */
    public function getStockInBranch(int $branchId): float
    {
        return $this->branchStock()
            ->where('branch_id', $branchId)
            ->value('quantity') ?? 0;
    }

    /**
     * هل الكمية أقل من الحد الأدنى؟
     */
    public function isLowStock(int $branchId = null): bool
    {
        $quantity = $branchId
            ? $this->getStockInBranch($branchId)
            : $this->total_stock;

        return $quantity <= $this->min_stock;
    }

    /**
     * الحصول على الوحدة بالعربي
     */
    public function getUnitLabelAttribute(): string
    {
        return match($this->unit) {
            'meter' => 'متر',
            'board' => 'لوح',
            'sqm' => 'م²',
            'ton' => 'طن',
            'piece' => 'قطعة',
            'kg' => 'كيلوغرام',
            default => $this->unit,
        };
    }
}
