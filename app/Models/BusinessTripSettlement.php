<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BusinessTripSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_number',
        'business_trip_request_id',
        'user_id',
        'total_actual_cost',
        'advance_amount',
        'difference_amount',
        'trip_report',
        'status',
        'submitted_at',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'total_actual_cost' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function businessTripRequest(): BelongsTo
    {
        return $this->belongsTo(BusinessTripRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function expenseItems(): HasMany
    {
        return $this->hasMany(BusinessTripExpenseItem::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'trackable');
    }
}
