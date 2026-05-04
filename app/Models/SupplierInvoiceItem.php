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
        'm3_quantity', 'usd_price_per_m3', 'boards_per_m3',
        'customs_usd_price', 'customs_exchange_rate', 'customs_percentage',
        'total_boards', 'customs_amount', 'cost_per_board',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost_egp' => 'decimal:4',
        'cost_usd' => 'decimal:4',
        'dollar_rate' => 'decimal:4',
        'total_egp' => 'decimal:2',
        'm3_quantity' => 'decimal:4',
        'usd_price_per_m3' => 'decimal:2',
        'boards_per_m3' => 'decimal:2',
        'customs_usd_price' => 'decimal:2',
        'customs_exchange_rate' => 'decimal:2',
        'customs_percentage' => 'decimal:2',
        'total_boards' => 'decimal:2',
        'customs_amount' => 'decimal:2',
        'cost_per_board' => 'decimal:2',
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
