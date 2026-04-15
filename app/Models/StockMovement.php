<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'branch_id', 'to_branch_id', 'type',
        'quantity', 'quantity_before', 'quantity_after',
        'unit_cost', 'reference_type', 'reference_id',
        'user_id', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'unit_cost' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'شراء',
            'sale' => 'بيع',
            'transfer_in' => 'تحويل وارد',
            'transfer_out' => 'تحويل صادر',
            'adjustment_add' => 'تسوية إضافة',
            'adjustment_remove' => 'تسوية خصم',
            'return_to_supplier' => 'مرتجع للمورد',
            'return_from_customer' => 'مرتجع من عميل',
            default => $this->type,
        };
    }
}
