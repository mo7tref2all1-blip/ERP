<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'branch_id', 'supplier_invoice_item_id',
        'quantity', 'remaining_quantity', 'cost_egp',
        'cost_usd', 'dollar_rate', 'batch_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'remaining_quantity' => 'decimal:3',
        'cost_egp' => 'decimal:4',
        'cost_usd' => 'decimal:4',
        'dollar_rate' => 'decimal:4',
        'batch_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplierInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoiceItem::class);
    }

    /**
     * هل الدفعة فارغة؟
     */
    public function isEmpty(): bool
    {
        return $this->remaining_quantity <= 0;
    }
}
