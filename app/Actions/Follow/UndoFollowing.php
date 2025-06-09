<?php

namespace App\Actions\Follow;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Event;

class UndoFollowing
{
    /**
     * Handle the unfollowing logic.
     * Returns array with keys: ['success' => bool, 'message' => string]
     */
    public function __invoke(Request $request, User $user)
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
}
