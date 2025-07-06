<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\EventParticipation;
use Illuminate\Support\Facades\Http;

/*
 * Deprecated in new version
 */
final class RubyCronController extends Controller
{
    public function processUserPointsFromRuby($event_id)
    {
        $participants = EventParticipation::where('event_id', $event_id)
            ->whereHas('event')
            ->with(['user' => function ($query) {
                $query->select(['id', 'first_name', 'last_name', 'display_name', 'email']);
            }])
            ->get();

        $updatedUsers = [];
        $updateCount = 0;

        if ($participants->isEmpty()) {
            return response()->json(['message' => 'No participants found for this event'], 404);
        }

        foreach ($participants as $participant) {
            $response = Http::post('https://tracker.runtheedge.com/user_points/user_point_workflow', [
                'user_id' => $participant->user_id,
                'event_id' => $event_id,
            ]);

            if ($response->successful()) {
                $updateCount++;
                $updatedUsers[] = [
                    'user_id' => $participant->user_id,
                    'name' => $participant->user->display_name,
                    'email' => $participant->user->email,
                    'status' => 'updated',
                ];
            }
        }

        return response()->json([
            'message' => 'Ruby cron job executed successfully',
            // 'total_participants' => $participants->count(),
            // 'total_updated' => $updateCount,
            // 'updated_users' => $updatedUsers
        ]);
    }
}
