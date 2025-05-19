<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait for handling user event participation queries
 *
 * This trait provides functionality to retrieve user participation
 * in events based on date and event criteria.
 */
trait UserEventParticipationTrait
{
    /**
     * Get user participation for a specific date and optional event
     *
     * @param  mixed  $user  The user object to query participation for
     * @param  string|null  $date  The date to check participation (defaults to current date)
     * @param  int|null  $eventId  Optional event ID to filter participation
     * @return Collection Collection of participation records
     */
    public function userParticipations(mixed $user, ?string $date = null, ?int $eventId = null): Collection
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');

        return $user->participations()
            ->with('event')
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
    }
}
