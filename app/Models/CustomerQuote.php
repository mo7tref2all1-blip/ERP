<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerQuote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'quote_number', 'quote_date', 'valid_until',
        'branch_id', 'total_amount', 'discount_amount', 'net_amount',
        'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerQuoteItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(CustomerInvoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'sent' => 'مُرسل',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
            'expired' => 'منتهي',
            default => $this->status,
        };
    }

    /**
     * تحويل عرض السعر إلى فاتورة بيع
     */
    public function convertToInvoice(): CustomerInvoice
    {
        $invoice = CustomerInvoice::create([
            'customer_id' => $this->customer_id,
            'customer_quote_id' => $this->id,
            'invoice_number' => CustomerInvoice::generateNumber(),
            'invoice_date' => now(),
            'branch_id' => $this->branch_id,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'net_amount' => $this->net_amount,
            'status' => 'unpaid',
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ]);

        foreach ($this->items as $item) {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'default_price' => $item->default_price,
                'unit_price' => $item->unit_price,
                'discount_percent' => $item->discount_percent,
                'total' => $item->total,
            ]);
        }

        $this->update(['status' => 'accepted']);

        return $invoice;
    }
}
