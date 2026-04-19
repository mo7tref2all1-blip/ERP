<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id', 'invoice_number', 'invoice_date', 'due_date',
        'branch_id', 'total_amount', 'paid_amount', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
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
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * تحديث حالة الدفع تلقائياً
     */
    protected static function booted(): void
    {
        static::deleting(function (SupplierInvoice $invoice) {
            foreach ($invoice->items as $item) {
                \App\Models\BranchStock::where('product_id', $item->product_id)
                    ->where('branch_id', $invoice->branch_id)
                    ->decrement('quantity', $item->quantity);
                \App\Models\StockBatch::where('supplier_invoice_item_id', $item->id)->delete();
            }
            \App\Models\StockMovement::where('reference_type', SupplierInvoice::class)
                ->where('reference_id', $invoice->id)
                ->delete();
            $invoice->payments()->delete();
            \App\Models\AccountTransaction::where('reference_type', SupplierInvoice::class)
                ->where('reference_id', $invoice->id)
                ->delete();
        });
    }

    public function updatePaymentStatus(): void
    {
        $paid = $this->payments()->sum('amount');
        $this->paid_amount = $paid;

        if ($paid >= $this->total_amount) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}
