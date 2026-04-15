<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'name', 'type', 'bank_name', 'account_number',
        'opening_balance', 'notes', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * حساب الرصيد الحالي
     */
    public function getCurrentBalanceAttribute(): float
    {
        $inflow = $this->transactions()->where('type', 'in')->sum('amount');
        $outflow = $this->transactions()->where('type', 'out')->sum('amount');
        return $this->opening_balance + $inflow - $outflow;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'bank' ? 'بنك' : 'خزينة نقدية';
    }
}
