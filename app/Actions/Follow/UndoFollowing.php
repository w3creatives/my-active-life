<?php

namespace App\Actions\Follow;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Event;
use App\Models\Team;

class UndoFollowing
{
    /**
     * Handle the unfollowing logic for both users and teams.
     * Returns array with keys: ['success' => bool, 'message' => string]
     */
    public function __invoke(Request $request, User $user)
    {
        // Determine if this is a user or team unfollow based on the URL
        $type = $request->route('type'); // This gets the {type} from the route

        if ($type === 'user') {
            return $this->unfollowUser($request, $user);
        } elseif ($type === 'team') {
            return $this->unfollowTeam($request, $user);
        }

        return [
            'success' => false,
            'message' => 'Invalid unfollow type',
        ];
    }

    /**
     * Handle unfollowing a user.
     */
    private function unfollowUser(Request $request, User $user)
    {
        $request->validate([
            'user_id' => ['required', Rule::exists((new User)->getTable(), 'id')],
            'event_id' => ['required', Rule::exists((new Event)->getTable(), 'id')],
        ]);

        $following = $user->following()
            ->where('event_id', $request->event_id)
            ->where('followed_id', $request->user_id)
            ->first();

        if (is_null($following)) {
            return [
                'success' => false,
                'message' => 'Invalid action',
            ];
        }

        // Delete any pending follow requests for that user and event
        $user->followingRequests()
            ->where(['event_id' => $request->event_id, 'followed_id' => $request->user_id])
            ->delete();

        $following->delete();

        return [
            'success' => true,
            'message' => 'Unfollowed successfully',
        ];
    }

    /**
     * Handle unfollowing a team.
     */
    private function unfollowTeam(Request $request, User $user)
    {
        $request->validate([
            'team_id' => ['required', Rule::exists((new Team)->getTable(), 'id')],
            'event_id' => ['required', Rule::exists((new Event)->getTable(), 'id')],
        ]);

        $team = Team::find($request->team_id);

        if (is_null($team)) {
            return [
                'success' => false,
                'message' => 'Team not found',
            ];
        }

        $teamFollow = $team->followers()
            ->where('follower_id', $user->id)
            ->where('event_id', $request->event_id)
            ->first();

        if (is_null($teamFollow)) {
            return [
                'success' => false,
                'message' => 'You are not following this team',
            ];
        }

        // Delete any pending follow requests for that team and event
        $team->followerRequests()
            ->where(['prospective_follower_id' => $user->id, 'event_id' => $request->event_id])
            ->delete();

        $teamFollow->delete();

        return [
            'success' => true,
            'message' => 'Team unfollowed successfully',
        ];
    }
}
