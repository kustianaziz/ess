<?php

namespace App\Actions\Shared;

use Illuminate\Support\Facades\DB;

class GenerateRequestNumberAction
{
    /**
     * Generate unique request number with format: {PREFIX}/{YEAR}/{MONTH}/{COUNTER}
     * Example: RB/2026/07/0001
     */
    public function execute(string $prefix, string $tableName): string
    {
        $year = date('Y');
        $month = date('m');
        $searchPrefix = "{$prefix}/{$year}/{$month}/";

        return DB::transaction(function () use ($searchPrefix, $tableName) {
            $latest = DB::table($tableName)
                ->where('request_number', 'like', "{$searchPrefix}%")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($latest) {
                $lastNumber = (int) substr($latest->request_number, strrpos($latest->request_number, '/') + 1);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            return sprintf('%s%04d', $searchPrefix, $nextNumber);
        });
    }
}
