<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    protected $fillable = [
        'customer_id', 'customer_invoice_id', 'bank_account_id',
        'amount', 'payment_date', 'reference_number', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
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
        static::created(function (CustomerPayment $payment) {
            AccountTransaction::create([
                'bank_account_id' => $payment->bank_account_id,
                'type' => 'in',
                'amount' => $payment->amount,
                'reference_type' => CustomerPayment::class,
                'reference_id' => $payment->id,
                'description' => 'دفعة من العميل: ' . $payment->customer->name,
                'transaction_date' => $payment->payment_date,
                'created_by' => $payment->created_by,
            ]);

            if ($payment->customer_invoice_id) {
                $payment->invoice->updatePaymentStatus();
            }
        });

        static::deleted(function (CustomerPayment $payment) {
            AccountTransaction::where('reference_type', CustomerPayment::class)
                ->where('reference_id', $payment->id)
                ->delete();

            if ($payment->customer_invoice_id) {
                $payment->invoice->updatePaymentStatus();
            }
        });
    }
}
