<?php

namespace App\Traits;

use Carbon\CarbonImmutable;

trait CalculateDays
{

    public function daysFromStartEndDate($startDate, $endDate)
    {
        $startDate = CarbonImmutable::parse($startDate);
        $endDate = $endDate ? CarbonImmutable::parse($endDate) : $startDate;

        return [
            $startDate,
            $endDate,
            $startDate->diffInDays($endDate, true)
        ];
    }
}
