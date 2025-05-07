<?php

namespace App\Traits;

use Carbon\Carbon;

trait UserEventParticipationTrait
{

    public function userParticipations($user, $date = null, $eventId = null)
    {

        $date = $date ?? Carbon::now()->format('Y-m-d');

        $participations = $user->participations()
            ->where('subscription_end_date', '>=', $date)
            ->where(function ($query) use ($eventId) {

                if ($eventId) {
                    $query->where('event_id', $eventId);
                }

                return $query;
            })
            ->whereHas('event', function ($query) use ($date) {
                return $query->where('start_date', '<=', $date);
            })->get();

        return $participations;
    }
}
