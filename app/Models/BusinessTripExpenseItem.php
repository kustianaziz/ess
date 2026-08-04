<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessTripExpenseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_trip_settlement_id',
        'category',
        'description',
        'amount',
        'expense_date',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(BusinessTripSettlement::class, 'business_trip_settlement_id');
    }
}
