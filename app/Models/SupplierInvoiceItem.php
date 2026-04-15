<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplierInvoiceItem extends Model
{
    protected $fillable = [
        'supplier_invoice_id', 'product_id', 'quantity',
        'cost_egp', 'cost_usd', 'dollar_rate', 'total_egp', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost_egp' => 'decimal:4',
        'cost_usd' => 'decimal:4',
        'dollar_rate' => 'decimal:4',
        'total_egp' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch(): HasOne
    {
        return $this->hasOne(StockBatch::class);
    }
}
