<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Team;
use App\Models\User;

final class EventMilestones
{
    private const BASE_IMAGE_PATH = 'images';

    /**
     * Get all milestones for an event with images and completion status
     *
     * @param  int  $eventId  The event ID
     * @param  int  $userId  The user ID to check completion status
     * @return array Array of milestones with completion status
     */
    public function getEventMilestonesWithStatus(int $eventId, int $userId): array
    {
        $event = Event::find($eventId);

        if (! $event) {
            return [
                'error' => 'Event not found',
                'status' => false,
            ];
        }

        $user = User::find($userId);

        if (! $user) {
            return [
                'error' => 'User not found',
                'status' => false,
            ];
        }

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        // Get user's total points for this event
        $userTotalPoints = $user->totalPoints()
            ->where('event_id', $eventId)
            ->first();

        $userDistance = $userTotalPoints ? (float) $userTotalPoints->amount : 0;

        // Get team's total points for this event
        $teamTotalPoints = $team ? $team->totalPoints()->where('event_id', $eventId)->first() : null;

        $teamDistance = $teamTotalPoints ? (float) $teamTotalPoints->amount : 0;

        $result = [];

        if ($event->event_type === 'fit_life') {
            // Handle FitLife event type
            return $this->getFitLifeMilestonesWithStatus($event, $user, $team);
        }
        // Handle regular event types
        $milestones = $event->milestones()->orderBy('distance', 'asc')->get();

        // Eager load all displayed milestones for this user and event to avoid N+1 queries
        $milestoneIds = $milestones->pluck('id')->toArray();
        $displayedMilestones = $user->displayedMilestones()
            ->whereIn('event_milestone_id', $milestoneIds)
            ->get()
            ->groupBy(function ($item) {
                return $item->event_milestone_id.'_'.$item->individual;
            });

        foreach ($milestones as $milestone) {
            $isCompleted = $userDistance >= $milestone->distance;
            $isTeamCompleted = $teamDistance >= $milestone->distance;

            // Calculate actual earned date for individual milestone
            $userEarnedAt = null;
            if ($isCompleted) {
                $userEarnedAt = $this->calculateMilestoneEarnedDate($user, $event, (float) $milestone->distance);
            }

            // Calculate actual earned date for team milestone
            $teamEarnedAt = null;
            if ($isTeamCompleted && $team) {
                $teamEarnedAt = $this->calculateTeamMilestoneEarnedDate($team, $event, (float) $milestone->distance);
            }

            $milestoneData = [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'description' => $milestone->description,
                'distance' => $milestone->distance,
                'is_completed' => $isCompleted,
                'logo_image_url' => $milestone->logo,
                'team_logo_image_url' => $milestone->team_logo,
                'video_url' => $milestone->video_url,
                'is_team_completed' => $isTeamCompleted,
                'earned_at' => $userEarnedAt?->toIso8601String(),
                'team_earned_at' => $teamEarnedAt?->toIso8601String(),
            ];

            $result[] = $milestoneData;
        }

        return [
            'status' => true,
            'milestones' => $result,
        ];
    }

    /**
     * Get FitLife milestones with completion status
     *
     * @param  Event  $event  The event
     * @param  User  $user  The user
     * @return array Array of FitLife milestones with completion status
     */
    private function getFitLifeMilestonesWithStatus(Event $event, User $user, $team): array
    {
        $result = [];

        // Get user's FitLife registrations for this event
        $registrations = $user->fitLifeRegistrations()
            ->whereHas('activity', function ($query) use ($event) {
                return $query->where('event_id', $event->id);
            })
            ->get();

        foreach ($registrations as $registration) {
            $activity = $registration->activity;

            // Get user's points for this activity
            $userPoints = $user->points()
                ->where('event_id', $event->id)
                ->where('date', $registration->date)
                ->sum('amount');

            $teamPoints = 0;

            if ($team) {
                $teamPoints = $team->points()->where('event_id', $event->id)
                    ->where('date', $registration->date)
                    ->sum('amount');
            }

            // Get milestones for this activity
            $milestones = $activity->milestones()->orderBy('total_points', 'asc')->get();

            foreach ($milestones as $milestone) {
                $isCompleted = $userPoints >= $milestone->total_points;
                $isTeamCompleted = $teamPoints >= $milestone->total_points;
                // Get milestone images
                // $images = $this->getMilestoneImage($event->id, $milestone->total_points, $activity->id);

                $milestoneData = [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'description' => $milestone->description,
                    'total_points' => $milestone->total_points,
                    'is_completed' => $isCompleted,
                    'is_team_completed' => $isTeamCompleted,
                    'activity_id' => $activity->id,
                    'activity_name' => $activity->name,
                    'logo_image_url' => $milestone->logo,
                    'team_logo_image_url' => $milestone->team_logo,
                    'video_url' => $milestone->video_url,
                    // 'bib_image' => $this->getBibImage($event->id, $activity->id, $isCompleted),
                    // 'images' => $images,
                ];

                $result[] = $milestoneData;
            }
        }

        return [
            'status' => true,
            'milestones' => $result,
        ];
    }

    /**
     * Calculate when a user earned a milestone by finding the first date
     * their cumulative points reached or exceeded the milestone distance
     *
     * @param  User  $user  The user
     * @param  Event  $event  The event
     * @param  float  $milestoneDistance  The milestone distance
     * @return \Carbon\Carbon|null The date when the milestone was earned
     */
    private function calculateMilestoneEarnedDate(User $user, Event $event, float $milestoneDistance): ?\Carbon\Carbon
    {
        // Get all user points for this event ordered by date
        $points = $user->points()
            ->where('event_id', $event->id)
            ->where('date', '>=', $event->start_date)
            ->orderBy('date')
            ->get();

        $cumulativeDistance = 0;

        foreach ($points as $point) {
            $cumulativeDistance += $point->amount;

            // Check if this is the date the milestone was reached
            if ($cumulativeDistance >= $milestoneDistance) {
                return \Carbon\Carbon::parse($point->date);
            }
        }

        return null;
    }

    /**
     * Calculate when a team earned a milestone by finding the first date
     * their cumulative points reached or exceeded the milestone distance
     *
     * @param  Team  $team  The team
     * @param  Event  $event  The event
     * @param  float  $milestoneDistance  The milestone distance
     * @return \Carbon\Carbon|null The date when the milestone was earned
     */
    private function calculateTeamMilestoneEarnedDate(Team $team, Event $event, float $milestoneDistance): ?\Carbon\Carbon
    {
        // Get all team points for this event ordered by date
        $points = $team->points()
            ->where('event_id', $event->id)
            ->where('date', '>=', $event->start_date)
            ->orderBy('date')
            ->get();

        $cumulativeDistance = 0;

        foreach ($points as $point) {
            $cumulativeDistance += $point->amount;

            // Check if this is the date the milestone was reached
            if ($cumulativeDistance >= $milestoneDistance) {
                return \Carbon\Carbon::parse($point->date);
            }
        }

        return null;
    }
}
