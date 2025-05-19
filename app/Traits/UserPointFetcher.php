<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Provides methods for fetching and processing user activity points.
 *
 * This trait handles point queries, filtering by date ranges,
 * and processing of modality data.
 */
trait UserPointFetcher
{
    use RTEHelpers;

    /**
     * Fetch user points within a date range for a specific event
     *
     * @param  User  $user  The user to fetch points for
     * @param  string  $startDate  Start date in Y-m-d format
     * @param  string  $endDate  End date in Y-m-d format
     * @param  int  $eventId  The event ID to filter by
     * @param  string|null  $modality  Optional modality filter
     * @return array Array containing points, participation's and milestones
     */
    public function fetchUserPointsInDateRange(User $user, string $startDate, string $endDate, int $eventId, ?string $modality = null): array
    {
        $event = Event::find($eventId);

        $points = $this->fetchUserPoints($user, $startDate, $endDate, $eventId, $modality);
        $participations = $this->fetchUserParticipations($user);
        $milestones = $event->milestones()->get();

        return compact('points', 'participations', 'milestones');
    }

    public function fetchUserEventTotalPoints(User $user, int $eventId): float
    {
        return (float) $user->totalPoints()->where('event_id', $eventId)->first()?->amount ?? 0.0;
    }

    /**
     * Fetch user participation's with events
     *
     * @param  User  $user  The user to fetch participation's for
     * @return Collection Collection of user participation's
     */
    private function fetchUserParticipations(User $user): Collection
    {
        return $user->participations()->with('event')->get()->each(function ($participation) {
            if ($participation->event) {
                $participation->event['supported_modalities'] = $this->decodeModalities($participation->event['supported_modalities']);
            }
        });
    }

    /**
     * Fetch user points filtered by date range, event and modality
     *
     * @param  User  $user  The user to fetch points for
     * @param  string  $startDate  Start date in Y-m-d format
     * @param  string  $endDate  End date in Y-m-d format
     * @param  int  $eventId  The event ID to filter by
     * @param  string|null  $modality  Optional modality filter
     * @return Collection Collection of user points
     */
    private function fetchUserPoints(User $user, string $startDate, string $endDate, int $eventId, ?string $modality): Collection
    {
        return $user->points()->where('event_id', $eventId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where(function ($query) use ($modality) {
                if ($modality) {
                    $query->where('modality', $modality);
                }

                return $query;
            })
            ->get();
    }
}
