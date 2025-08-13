<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserPointRepository;
use App\Traits\UserEventParticipationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

final class UserPointService
{
    use UserEventParticipationTrait;

    private $userPointRepository;

    public function __construct(UserPointRepository $userPointRepository)
    {
        $this->userPointRepository = $userPointRepository;
    }

    public function createOrUpdate(object $user, array $point, bool $skipUpdate = false)
    {
        $point['date'] = Carbon::parse($point['date'])
            ->setTimezone($user->time_zone ?? 'UTC');

        $condition = $skipUpdate ? [] : [
            'date' => $point['date'],
            'modality' => $point['modality'],
            'event_id' => $point['eventId'],
            'data_source_id' => $point['dataSourceId'],
        ];

        $this->userPointRepository->create($user, $point, $condition);
    }

    public function last30DaysStats(User $user, int $eventId): array
    {
        // Calculate date range
        $endDate = Carbon::now()->format('Y-m-d');
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');

        // Get daily points for last 30 days
        $dailyPoints = $user->points()
            ->selectRaw('DATE(date) as date, SUM(amount) as daily_total')
            ->where('event_id', $eventId)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Create a collection of all dates in the range
        $dateRange = collect();
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);

        while ($currentDate <= $lastDate) {
            $dateRange->push([
                'date' => $currentDate->format('Y-m-d'),
                'daily_total' => 0,
                'seven_day_avg' => 0,
            ]);
            $currentDate->addDay();
        }

        // Map actual points to date range
        $dailyPoints->each(function ($point) use (&$dateRange) {
            $dateRange = $dateRange->map(function ($item) use ($point) {
                if ($item['date'] === $point->date) {
                    $item['daily_total'] = round((float) $point->daily_total, 2);
                }

                return $item;
            });
        });

        // Calculate 7-day rolling average
        $dateRange = $dateRange->map(function ($item, $key) use ($dateRange) {
            // Get previous 7 days including current day
            $sevenDayWindow = $dateRange->filter(function ($windowItem) use ($item) {
                $itemDate = Carbon::parse($item['date']);
                $windowDate = Carbon::parse($windowItem['date']);

                return $windowDate <= $itemDate &&
                    $windowDate > $itemDate->copy()->subDays(7);
            });

            $sevenDaySum = $sevenDayWindow->sum('daily_total');
            $item['seven_day_avg'] = round($sevenDaySum / min(7, $sevenDayWindow->count()), 2);

            return $item;
        });

        return [
            'stats' => $dateRange->values(),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }

    public function monthlies(int $eventId, User $user): Collection
    {
        return $this->userRepository->getMonthlyPoints($eventId, $user);
    }

    public function total(int $eventId, User $user): int
    {
        return $user->totalPoints()->where('event_id', $eventId)->sum('amount');
    }
}
