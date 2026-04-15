<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'address', 'type',
        'tax_number', 'opening_balance', 'notes', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(CustomerQuote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
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
     * الرصيد المتبقي (ما يدين به للشركة)
     */
    public function getBalanceAttribute(): float
    {
        return $this->total_invoices - $this->total_paid;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'company' ? 'شركة' : 'فرد';
    }
}
