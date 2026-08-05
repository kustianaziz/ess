<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function coaAsset()
    {
        return $this->belongsTo(Coa::class, 'coa_asset_id');
    }

    public function coaDepreciationExpense()
    {
        return $this->belongsTo(Coa::class, 'coa_depreciation_expense_id');
    }

    public function coaAccumulatedDepreciation()
    {
        return $this->belongsTo(Coa::class, 'coa_accumulated_depreciation_id');
    }

    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class);
    }
}
