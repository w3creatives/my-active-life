<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\DataSource;
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
     * @param User $user The user to fetch points for
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param int $eventId The event ID to filter by
     * @param string|null $modality Optional modality filter
     * @return array Array containing points, participation's and milestones
     */
    public function fetchUserPointsInDateRange(User $user, string $startDate, string $endDate, int $eventId, ?string $modality = null, bool $toArray = false): array
    {
        $event = Event::find($eventId);

        $points = $this->fetchUserPoints($user, $startDate, $endDate, $eventId, $modality);
        $participations = $this->fetchUserParticipations($user);
        $milestones = $event->milestones()->get();

        if ($toArray) {
            return [
                'points' => $points->toArray(),
                'participations' => $participations->toArray(),
                'milestones' => $milestones->toArray(),
            ];
        }

        return compact('points', 'participations', 'milestones');
    }

    public function fetchUserEventTotalPoints(User $user, int $eventId): float
    {
        return (float)$user->totalPoints()->where('event_id', $eventId)->first()?->amount ?? 0.0;
    }

    public function fetchUserDailyPoints(User $user, string $date, int $eventId, bool $toArray = false): Collection|array
    {
        $event = Event::find($eventId);

        $participation = $event->participations()->where(['user_id' => $user->id])->first();

        $modalities = $participation->modality_overrides;

        $dataSources = DataSource::query()->orderBy('name','ASC')->get();

        $data = [];

        foreach ($dataSources as $dataSource) {
            foreach ($modalities as $modality) {
                $points = $user->points()
                    ->where(['event_id' => $eventId, 'data_source_id' => $dataSource->id, 'modality' => $modality])
                    ->whereDate('date', $date)
                    ->sum('amount');

                if (!$points && $dataSource->short_name != 'manual') {
                    continue;
                }

                $data[$dataSource->short_name][] = ['modality' => $modality, 'points' => $points, 'data_source_id' => $dataSource->id];
            }
        }

        return ['dataSources' => $dataSources, 'items' => $data];
        //{"modality_overrides": ["daily_steps", "run", "walk"]}
        $points = $user->points()->where('event_id', $eventId)
            ->whereDate('date', $date)
            ->with('source')
            ->get()
            ->groupBy('data_source_id')
            ->map(function ($group) {
                // Group by modality and sum amounts
                return $group->groupBy('modality')
                    ->map(function ($modalityGroup) {
                        $first = $modalityGroup->first();
                        $first->amount = $modalityGroup->sum('amount');

                        return $first;
                    })
                    ->values(); // Convert to a numeric array
            });

        if ($toArray) {
            return $points->toArray();
        }

        return $points;
    }

    /**
     * Fetch user participation's with events
     *
     * @param User $user The user to fetch participation's for
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
     * @param User $user The user to fetch points for
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param int $eventId The event ID to filter by
     * @param string|null $modality Optional modality filter
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
            ->selectRaw('date, SUM(amount) as amount, MIN(id) as id, MIN(user_id) as user_id,
                         MIN(event_id) as event_id, MIN(data_source_id) as data_source_id,
                         MIN(modality) as modality, MIN(created_at) as created_at, MIN(updated_at) as updated_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
