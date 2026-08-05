<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MonthlyBillPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'bill_type_id',
        'period_month',
        'period_year',
        'bill_amount',
        'due_date',
        'payment_date',
        'status',
        'payment_reference',
        'notes',
        'submitted_by',
        'paid_by',
    ];

    protected $casts = [
        'bill_amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    public function billType(): BelongsTo
    {
        return $this->belongsTo(MonthlyBillType::class, 'bill_type_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
