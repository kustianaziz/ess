<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalReminder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'reminder_date' => 'date',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}