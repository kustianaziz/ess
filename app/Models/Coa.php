<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_header' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Coa::class, 'parent_id');
    }

    public function subCoas()
    {
        return $this->hasMany(Coa::class, 'parent_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'coa_asset_id');
    }

    public function depreciationExpenses()
    {
        return $this->hasMany(Asset::class, 'coa_depreciation_expense_id');
    }

    public function accumulatedDepreciations()
    {
        return $this->hasMany(Asset::class, 'coa_accumulated_depreciation_id');
    }
}
