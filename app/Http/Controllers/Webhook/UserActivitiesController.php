<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessUserDistanceTracker;
use App\Jobs\TriggerCelebrationMail;
use Illuminate\Http\Request;

class UserActivitiesController extends Controller
{
    /**
     * Queue a job to process user distance tracking
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userDistanceTracker(Request $request)
    {
        // Dispatch the job to process user distance tracking
        ProcessUserDistanceTracker::dispatch();

        return response()->json(['message' => 'User distance tracker job has been queued successfully']);
    }

    /**
     * Queue a job to trigger celebration emails
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function triggerCelebrationMail(Request $request)
    {
        // Dispatch the job to trigger celebration emails
        TriggerCelebrationMail::dispatch();

        return response()->json(['message' => 'Celebration mail job has been queued successfully']);
    }
}
