<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_id', 'supplier_invoice_id', 'bank_account_id',
        'amount', 'payment_date', 'reference_number', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::created(function (SupplierPayment $payment) {
            // تسجيل في حركات الحساب البنكي
            AccountTransaction::create([
                'bank_account_id' => $payment->bank_account_id,
                'type' => 'out',
                'amount' => $payment->amount,
                'reference_type' => SupplierPayment::class,
                'reference_id' => $payment->id,
                'description' => 'دفعة للمورد: ' . $payment->supplier->name,
                'transaction_date' => $payment->payment_date,
                'created_by' => $payment->created_by,
            ]);

            // تحديث حالة الفاتورة
            if ($payment->supplier_invoice_id) {
                $payment->invoice->updatePaymentStatus();
            }
        });

        static::deleted(function (SupplierPayment $payment) {
            AccountTransaction::where('reference_type', SupplierPayment::class)
                ->where('reference_id', $payment->id)
                ->delete();

            if ($payment->supplier_invoice_id) {
                $payment->invoice->updatePaymentStatus();
            }
        });
    }
}
