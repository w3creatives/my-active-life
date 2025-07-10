<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;

trait UserStreakManagerTrait
{
    public function processUserStreaks($user, $event): array
    {
        if ($event->event_type !== 'promotional') {
            return [];
        }

        /**
         * Remove user streaks
         */
        $user->userStreaks()->where(['event_id' => $event->id])->delete();

        $streaks = $event->streaks()->get();

        if (! $streaks->count()) {
            return [];
        }

        [$pointDates, $pointDateDistances] = $this->pointDateDistances($user, $event);

        $completedStreaks = [];

        foreach ($streaks as $streak) {
            $daysCount = (int) $streak->days_count;

            $minDistance = json_decode($streak->data)->min_distance;

            $streakSequence = [];

            foreach ($pointDates as $dateKey => $pointDate) {
                $nextDayDate = $pointDates[$dateKey + 1] ?? null;

                $totalPoint = $pointDateDistances[$pointDate];

                $currentDate = Carbon::parse($pointDate);

                $dayDifference = $nextDayDate ? (int) Carbon::parse($currentDate)->diffInDays($nextDayDate) : 1;

                $streakSequence[] = $pointDate;

                /**
                 * Match if next day date difference is 1 (for min days 2 ignore previous date) and points sum is equal or greater than streak's min distance
                 */
                if (! (($dayDifference === 1 || count($streakSequence) === $daysCount) && $totalPoint >= $minDistance)) {
                    $streakSequence = [];

                    continue;
                }

                if (count($streakSequence) === $daysCount) {
                    break;
                }
            }

            /**
             * Skip other streaks when first order days count match not available
             */
            if (count($streakSequence) !== $daysCount) {
                break;
            }

            /**
             * Create User Streaks
             */
            $completedStreaks[] = $this->createUserStreak($user, $streak, $streakSequence);
        }

        return $completedStreaks;
    }

    private function pointDateRange($user, $event): array
    {
        $userParticipation = $user->participations()->where(['event_id' => $event->id])->first();
        $eventEndDate = $userParticipation->subscription_end_date ? $userParticipation->subscription_end_date : $event->end_date;

        $startDate = Carbon::parse($event->start_date)->format('Y-m-d');
        $endDate = Carbon::parse($eventEndDate)->isFuture() ? Carbon::now()->format('Y-m-d') : Carbon::parse($eventEndDate)->format('Y-m-d');

        return [$startDate, $endDate];
    }

    private function pointDateDistances($user, $event): array
    {
        [$startDate, $endDate] = $this->pointDateRange($user, $event);
        $pointDateDistances = $user->points()
            ->selectRaw('sum(amount) as tamount,date')
            ->where(['event_id' => $event->id])
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->pluck('tamount', 'date')->toArray();

        $pointDates = array_keys($pointDateDistances);

        return [$pointDates, $pointDateDistances];
    }

    private function createUserStreak($user, $streak, $streakSequence)
    {
        $accomplishedDate = last($streakSequence);

        $user->userStreaks()->create([
            'date' => $accomplishedDate,
            'event_id' => $streak->event_id,
            'event_streak_id' => $streak->id,
        ]);

        $userDisplayedStreak = $user->displayedStreaks()->where(['event_streak_id' => $streak->id])->first();

        if (! is_null($userDisplayedStreak)) {
            $userDisplayedStreak->fill(['accomplished_date' => $accomplishedDate])->save();

            return $userDisplayedStreak;
        }

        return $user->displayedStreaks()->create([
            'event_streak_id' => $streak->id,
            'displayed' => false,
            'emailed' => false,
            'individual' => true,
            'accomplished_date' => $accomplishedDate,
        ]);
    }
}
