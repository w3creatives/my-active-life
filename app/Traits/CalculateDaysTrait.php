<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\CarbonImmutable;

trait CalculateDaysTrait
{
    public function daysFromStartEndDate($startDate, $endDate): array
    {
        $startDate = CarbonImmutable::parse($startDate);
        $endDate = $endDate ? CarbonImmutable::parse($endDate) : $startDate;

        return [
            $startDate,
            $endDate,
            $startDate->diffInDays($endDate, true),
        ];
    }
}
