<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'purchase_date' => 'date',
        'expired_date' => 'date',
        'price_customer' => 'decimal:2',
        'cost_vendor' => 'decimal:2',
        'auto_renew' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function renewalRequests()
    {
        return $this->hasMany(RenewalRequest::class);
    }

    public function renewalReminders()
    {
        return $this->hasMany(RenewalReminder::class);
    }
}