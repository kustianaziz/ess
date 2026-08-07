<?php

namespace App\Actions\Shared;

use Illuminate\Support\Facades\DB;

class GenerateRequestNumberAction
{
    /**
     * Generate unique request number with format: {PREFIX}/{YEAR}/{MONTH}/{COUNTER}
     * Example: RB/2026/07/0001
     */
    public function execute(string $prefix, string $tableName, string $columnName = 'request_number'): string
    {
        $year = date('Y');
        $month = date('m');
        $searchPrefix = "{$prefix}/{$year}/{$month}/";

        return DB::transaction(function () use ($searchPrefix, $tableName, $columnName) {
            $latest = DB::table($tableName)
                ->where($columnName, 'like', "{$searchPrefix}%")
                ->orderByDesc($columnName)
                ->lockForUpdate()
                ->first();

            if ($latest && isset($latest->{$columnName})) {
                $lastVal = $latest->{$columnName};
                $lastNumber = (int) substr($lastVal, strrpos($lastVal, '/') + 1);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            return sprintf('%s%04d', $searchPrefix, $nextNumber);
        });
    }
}
