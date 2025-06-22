<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Repositories\TeamRepository;

final class TeamService
{
    public function __construct(
        protected TeamRepository $teamRepository
    ) {}

    public function formatTeam($team, $user)
    {
        if (is_null($team)) {
            return null;
        }

        $team->is_team_owner = $team->owner_id === $user->id;

        $eventId = $team->event_id;

        $membershipStatus = null;
        if ($team->requests()->where(['prospective_member_id' => $user->id, 'event_id' => $eventId])->count()) {
            $membershipStatus = 'RequestedJoin';
        } elseif ($team->memberships()->where(['user_id' => $user->id, 'event_id' => $eventId])->count()) {
            $membershipStatus = 'Joined';
        } elseif ($team->invites()->where(['prospective_member_id' => $user->id, 'event_id' => $eventId])->count()) {
            $membershipStatus = 'JoinInProcess';
        }
        $team->membership_status = $membershipStatus;

        unset($team->requests);
        unset($team->memberships);
        unset($team->invites);
        unset($team->owner_id);

        return $team;
    }

    public function find($id)
    {
        return $this->teamRepository->find($id);
    }

    public function achievements($event, $dateRange, $team)
    {
        return $this->teamRepository->achievements($event, $dateRange, $team);
    }

    public function followingRequests(User $user, int $eventId, int $page = 1)
    {
        return $user->teamFollowingRequests()->where('event_id', $eventId)->with('team')->simplePaginate(100);
    }

    public function all($user, $eventId, $searchTerm = '', $listType = 'all', $perPage = 20, string $source = 'api')
    {
        $columns = ['id', 'name', 'public_profile', 'settings', 'owner_id', 'event_id'];

        $paginationArgs = $source === 'web'
            ? [$perPage, ['*'], 'teamsPage']
            : [$perPage];

        return $teams = Team::where(function ($query) use ($user, $listType, $searchTerm) {
            switch ($listType) {
                case 'own':
                    $query->where('owner_id', $user->id);
                    break;
                case 'other':
                    $query->where('owner_id', '!=', $user->id);
                    break;
                case 'joined':
                    $query->whereHas('memberships', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                    break;
            }

            if ($searchTerm) {
                $query->where('name', 'ILIKE', "{$searchTerm}%");
            }

            return $query;
        })
            /*
            whereHas('memberships', function($query) use($user) {
                        return $query->where('user_id', $user->id);
                    })
                   ->
            */
            ->where(function ($query) use ($eventId) {
                if (! $eventId) {
                    return $query;
                }

                return $query->where('event_id', $eventId);
            })
            ->orderBy('name', 'asc')
            ->simplePaginate(...$paginationArgs)
            ->through(function ($team) use ($user, $eventId) {
                $team->is_team_owner = $team->owner_id === $user->id;

                $membershipStatus = null;
                if ($team->requests()->where(['prospective_member_id' => $user->id, 'event_id' => $eventId])->count()) {
                    $membershipStatus = 'RequestedJoin';
                } elseif ($team->memberships()->where(['user_id' => $user->id, 'event_id' => $eventId])->count()) {
                    $membershipStatus = 'Joined';
                } elseif ($team->invites()->where(['prospective_member_id' => $user->id, 'event_id' => $eventId])->count()) {
                    $membershipStatus = 'JoinInProcess';
                }
                $team->membership_status = $membershipStatus;
                unset($team->requests);
                unset($team->memberships);
                unset($team->invites);
                unset($team->owner_id);
                $team->total_members = $team->memberships()->where('event_id', $eventId)->count();
                $team->total_miles = (float) $team->totalPoints()->where('event_id', $eventId)->sum('amount');

                return $team;
            });
    }

    /**
     * Returns teams that the user is following
     */
    public function following(User $user, int $eventId, int $perPage = 100, string $source = 'api')
    {
        $paginationArgs = $source === 'web'
            ? [$perPage, ['*'], 'teamFollowingPage']
            : [$perPage];

        return $user->teamFollowings()
            ->where('event_id', $eventId)
            ->with('team')
            ->simplePaginate(...$paginationArgs)
            ->through(function ($item) {
                $team = $item->team;
                $teamTotalDistance = (float) $item->team->totalPoints()->where('event_id', $team->event_id)->sum('amount');
                $teamTotalPoint = (float) $team->event->total_points;

                if ($teamTotalDistance > $teamTotalPoint) {
                    $teamTotalDistance = $teamTotalPoint;
                }

                $pendingDistance = $teamTotalPoint - $teamTotalDistance;
                $progressPercentage = ($teamTotalDistance / $teamTotalPoint) * 100;

                $item->statistics = [
                    'distance_total' => round($teamTotalPoint, 2),
                    'distance_completed' => round($teamTotalDistance, 2),
                    'distance_remaining' => round($pendingDistance, 2),
                    'progress_percentage' => round($progressPercentage, 2),
                ];

                return $item;
            });
    }
}
