<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class);
    }
}
