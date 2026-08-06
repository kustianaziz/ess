<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyBillType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'vendor_name',
        'default_amount',
        'billing_day',
        'cash_account_id',
        'is_active',
        'end_date',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'end_date' => 'date',
    ];

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MonthlyBillPayment::class, 'bill_type_id');
    }
}
