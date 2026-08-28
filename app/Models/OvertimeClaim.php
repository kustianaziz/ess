<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'claim_number',
        'overtime_request_id',
        'user_id',
        'actual_start_time',
        'actual_end_time',
        'amount',
        'level2_approver_id',
        'status',
        'current_approval_level',
        'rejected_reason',
        'submitted_at',
        'paid_by',
        'paid_at',
        'payment_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'paid_at' => 'datetime',
        'status' => RequestStatus::class,
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(OvertimeRequest::class, 'overtime_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level2Approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level2_approver_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
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
