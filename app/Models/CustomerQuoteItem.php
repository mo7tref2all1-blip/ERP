<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuoteItem extends Model
{
    protected $fillable = [
        'customer_quote_id', 'product_id', 'quantity',
        'default_price', 'unit_price', 'discount_percent', 'total', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'default_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class, 'customer_quote_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
