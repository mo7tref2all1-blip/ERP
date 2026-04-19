<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'customer_quote_id', 'invoice_number', 'invoice_date',
        'due_date', 'branch_id', 'total_amount', 'discount_amount', 'net_amount',
        'paid_amount', 'total_cost_fifo', 'profit_amount', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'total_cost_fifo' => 'decimal:2',
        'profit_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class, 'customer_quote_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'unpaid' => 'غير مدفوعة',
            'partial' => 'مدفوعة جزئياً',
            'paid' => 'مدفوعة بالكامل',
            default => $this->status,
        };
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->net_amount - $this->paid_amount;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->net_amount <= 0) return 0;
        return ($this->profit_amount / $this->net_amount) * 100;
    }

    /**
     * توليد رقم فاتورة فريد
     */
    protected static function booted(): void
    {
        static::deleting(function (CustomerInvoice $invoice) {
            foreach ($invoice->items as $item) {
                \App\Models\BranchStock::where('product_id', $item->product_id)
                    ->where('branch_id', $invoice->branch_id)
                    ->increment('quantity', $item->quantity);
            }
            \App\Models\StockMovement::where('reference_type', CustomerInvoice::class)
                ->where('reference_id', $invoice->id)
                ->delete();
            $invoice->payments()->delete();
            \App\Models\AccountTransaction::where('reference_type', CustomerInvoice::class)
                ->where('reference_id', $invoice->id)
                ->delete();
        });
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastInvoice
            ? (intval(substr($lastInvoice->invoice_number, -4)) + 1)
            : 1;

        return 'INV-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * تحديث حالة الدفع
     */
    public function updatePaymentStatus(): void
    {
        $paid = $this->payments()->sum('amount');
        $this->paid_amount = $paid;

        if ($paid >= $this->net_amount) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}
