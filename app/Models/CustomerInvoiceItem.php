<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInvoiceItem extends Model
{
    protected $fillable = [
        'customer_invoice_id', 'product_id', 'quantity',
        'default_price', 'unit_price', 'discount_percent',
        'total', 'cost_egp_fifo', 'profit', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'default_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'total' => 'decimal:2',
        'cost_egp_fifo' => 'decimal:4',
        'profit' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->total <= 0) return 0;
        return ($this->profit / $this->total) * 100;
    }
}
