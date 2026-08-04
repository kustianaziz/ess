<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessTripRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_number',
        'user_id',
        'assignment_letter_number',
        'destination',
        'purpose',
        'start_date',
        'end_date',
        'transportation_type',
        'estimated_budget',
        'status',
        'current_approval_level',
        'submitted_at',
        'rejected_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'estimated_budget' => 'decimal:2',
        'submitted_at' => 'datetime',
        'status' => RequestStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(BusinessTripSettlement::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'trackable');
    }
}
