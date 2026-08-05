<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'old_expired_date' => 'date',
        'new_expired_date' => 'date',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function vendorPayment()
    {
        return $this->belongsTo(VendorPayment::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}