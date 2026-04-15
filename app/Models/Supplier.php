<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'address', 'bank_account',
        'tax_number', 'opening_balance', 'notes', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * إجمالي الفواتير
     */
    public function getTotalInvoicesAttribute(): float
    {
        return $this->invoices()->sum('net_amount') + $this->opening_balance;
    }

    /**
     * إجمالي المدفوع
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * الرصيد المتبقي
     */
    public function getBalanceAttribute(): float
    {
        return $this->total_invoices - $this->total_paid;
    }
}
