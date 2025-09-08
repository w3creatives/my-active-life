<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Team;
use App\Models\User;

final class EventMilestones
{
    private const BASE_IMAGE_PATH = 'images';

    // Existing methods...

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

        foreach ($milestones as $milestone) {
            $isCompleted = $userDistance >= $milestone->distance;
            $isTeamCompleted = $teamDistance >= $milestone->distance;

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
}
