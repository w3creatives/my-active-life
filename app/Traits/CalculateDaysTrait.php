<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\CarbonImmutable;

/**
 * Trait for calculating days between dates
 *
 * This trait provides functionality to calculate the number of days
 * between two dates and return the parsed date objects.
 */
trait CalculateDaysTrait
{
    /**
     * Calculate days between start and end date
     *
     * @param  string|null  $startDate  The start date string
     * @param  string|null  $endDate  The end date string (defaults to start date if null)
     * @return array Returns array containing [startDate object, endDate object, days difference]
     */
    public function daysFromStartEndDate(?string $startDate, ?string $endDate): array
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
