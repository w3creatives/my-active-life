<?php

declare(strict_types=1);

namespace App\Actions\Follow;

use App\Models\Event;
use App\Models\EventParticipation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class RequestFollow
{
    /**
     * Handle the follow request logic for both users and teams.
     * Returns array with keys: ['success' => bool, 'message' => string, 'data' => array]
     */
    public function __invoke(Request $request, User $user, string $type)
    {
        if ($type === 'user') {
            return $this->requestFollowUser($request, $user);
        }
        if ($type === 'team') {
            return $this->requestFollowTeam($request, $user);
        }

        return [
            'success' => false,
            'message' => 'Invalid follow type',
            'data' => [],
        ];
    }

    /**
     * Handle requesting to follow a user.
     */
    private function requestFollowUser(Request $request, User $user)
    {
        $request->validate([
            'user_id' => ['required', Rule::exists((new User)->getTable(), 'id')],
            'event_id' => ['required', Rule::exists((new Event)->getTable(), 'id')],
        ]);

        // Check if the target user is participating in the event
        $memberParticipation = EventParticipation::where('event_id', $request->event_id)
            ->where('user_id', $request->user_id)
            ->first();

        if (! $memberParticipation) {
            return [
                'success' => false,
                'message' => 'User is not participating in this event',
                'data' => [],
            ];
        }

        // Check if the current user is participating in the event
        $userParticipation = $user->participations()
            ->where('event_id', $request->event_id)
            ->first();

        if (! $userParticipation) {
            return [
                'success' => false,
                'message' => 'You are not participating in this event',
                'data' => [],
            ];
        }

        // Check if user is trying to follow themselves
        if ($user->id === $request->user_id) {
            return [
                'success' => false,
                'message' => 'You cannot follow yourself',
                'data' => [],
            ];
        }

        $targetUser = User::find($request->user_id);

        // Check if already following
        $existingFollow = $user->following()
            ->where('event_id', $request->event_id)
            ->where('followed_id', $request->user_id)
            ->first();

        if ($existingFollow) {
            return [
                'success' => false,
                'message' => 'You are already following this user',
                'data' => [],
            ];
        }

        // Check if there's already a pending request
        $existingRequest = $user->followingRequests()
            ->where('followed_id', $request->user_id)
            ->where('event_id', $request->event_id)
            ->first();

        if ($existingRequest) {
            return [
                'success' => false,
                'message' => 'Follow request already sent',
                'data' => [],
            ];
        }

        // If target user has public profile, follow immediately
        if ($targetUser->public_profile) {
            $user->following()->create([
                'followed_id' => $request->user_id,
                'event_id' => $request->event_id,
            ]);

            return [
                'success' => true,
                'message' => 'Now following '.$targetUser->display_name,
                'data' => [
                    'follow_status' => 'following',
                    'follow_status_text' => 'Following',
                ],
            ];
        }

        // Create follow request for private profile
        $user->followingRequests()->create([
            'followed_id' => $request->user_id,
            'event_id' => $request->event_id,
            'status' => 'request_to_follow_issued',
        ]);

        return [
            'success' => true,
            'message' => 'Follow request sent to '.$targetUser->display_name,
            'data' => [
                'follow_status' => 'request_to_follow_issued',
                'follow_status_text' => 'Requested',
            ],
        ];
    }

    /**
     * Handle requesting to follow a team.
     */
    private function requestFollowTeam(Request $request, User $user)
    {
        $request->validate([
            'team_id' => ['required', Rule::exists((new Team)->getTable(), 'id')],
            'event_id' => ['required', Rule::exists((new Event)->getTable(), 'id')],
        ]);

        $team = Team::where(['id' => $request->team_id, 'event_id' => $request->event_id])->first();

        if (! $team) {
            return [
                'success' => false,
                'message' => 'Team not found',
                'data' => [],
            ];
        }

        // Check if the current user is participating in the event
        $userParticipation = $user->participations()
            ->where('event_id', $request->event_id)
            ->first();

        if (! $userParticipation) {
            return [
                'success' => false,
                'message' => 'You are not participating in this event',
                'data' => [],
            ];
        }

        // Check if user is trying to follow their own team
        if ($team->owner_id === $user->id) {
            return [
                'success' => false,
                'message' => 'You cannot follow your own team',
                'data' => [],
            ];
        }

        // Check if already following
        $existingFollow = $team->followers()
            ->where('follower_id', $user->id)
            ->where('event_id', $request->event_id)
            ->first();

        if ($existingFollow) {
            return [
                'success' => false,
                'message' => 'You are already following this team',
                'data' => [],
            ];
        }

        // Check if there's already a pending request
        $existingRequest = $team->followerRequests()
            ->where('prospective_follower_id', $user->id)
            ->where('event_id', $request->event_id)
            ->first();

        if ($existingRequest) {
            return [
                'success' => false,
                'message' => 'Follow request already sent',
                'data' => [],
            ];
        }

        // If team has public profile, follow immediately
        if ($team->public_profile) {
            $team->followers()->create([
                'follower_id' => $user->id,
                'event_id' => $request->event_id,
            ]);

            return [
                'success' => true,
                'message' => 'Now following '.$team->name,
                'data' => [
                    'follow_status' => 'following',
                    'follow_status_text' => 'Following',
                ],
            ];
        }

        // Create follow request for private team
        $team->followerRequests()->create([
            'prospective_follower_id' => $user->id,
            'event_id' => $request->event_id,
            'status' => 'request_to_follow_issued',
        ]);

        return [
            'success' => true,
            'message' => 'Follow request sent to '.$team->name,
            'data' => [
                'follow_status' => 'request_to_follow_issued',
                'follow_status_text' => 'Requested',
            ],
        ];
    }
}
