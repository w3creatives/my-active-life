<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;

trait UserStreakManagerTrait
{
    public function processUserStreaks($user, $event): bool
    {
        $streaks = $event->streaks()->get();

        $user->userStreaks()->where(['event_id' => $event->id])->delete();

        if (! $streaks->count()) {
            return false;
        }

        $userParticipation = $user->participations()->where(['event_id' => $event->id])->first();
        $eventEndDate = $userParticipation->subscription_end_date ? $userParticipation->subscription_end_date : $event->end_date;

        $startDate = Carbon::parse($event->start_date)->format('Y-m-d');
        $endDate = Carbon::parse($eventEndDate)->isFuture() ? Carbon::now()->format('Y-m-d') : Carbon::parse($eventEndDate)->format('Y-m-d');

        $pointDateDistances = $user->points()
            ->selectRaw('sum(amount) as tamount,date')
            ->where(['event_id' => $event->id])
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('date')
            ->pluck('tamount', 'date')->toArray();

        $pointDates = array_keys($pointDateDistances);

        $streakExecuted = false;

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

                if (! (($dayDifference === 1 || count($streakSequence) === $daysCount) && $totalPoint >= $minDistance)) {
                    $streakSequence = [];
                }

                if (count($streakSequence) === $daysCount) {
                    break;
                }
            }

            if (count($streakSequence) !== $daysCount) {
                break;
            }

            $accomplishedDate = last($streakSequence);

            $user->userStreaks()->create([
                'date' => $accomplishedDate,
                'event_id' => $event->id,
                'event_streak_id' => $streak->id,
            ]);

            $userDisplayedStreak = $user->displayedStreaks()->where(['event_streak_id' => $streak->id])->first();

            if (is_null($userDisplayedStreak)) {
                $user->displayedStreaks()->create(['event_streak_id' => $streak->id, 'displayed' => false, 'emailed' => false, 'individual' => true, 'accomplished_date' => $accomplishedDate]);
            } else {
                $userDisplayedStreak->fill(['accomplished_date' => $accomplishedDate])->save();
            }

            $streakExecuted = true;
        }

        return $streakExecuted;
    }
}
