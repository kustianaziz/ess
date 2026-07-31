<?php

namespace App\Actions\Leave;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalculateLeaveDaysAction
{
    /**
     * Calculate working days excluding Saturdays and Sundays.
     */
    public function execute(string|Carbon $startDate, string|Carbon $endDate): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return 0;
        }

        $period = CarbonPeriod::create($start, $end);
        $totalDays = 0;

        foreach ($period as $date) {
            if (!$date->isWeekend()) {
                $totalDays++;
            }
        }

        return $totalDays;
    }
}
