<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        try {
            if (!\Schema::hasTable('settings')) {
                return $default;
            }
            $setting = self::where('key', $key)->first();
            return $setting ? json_decode($setting->value, true) : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public static function set(string $key, $value)
    {
        try {
            if (!\Schema::hasTable('settings')) {
                return null;
            }
            return self::updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($value)]
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
