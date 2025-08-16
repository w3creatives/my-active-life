<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\EventParticipation;
use App\Services\EventService;
use Illuminate\Http\Request;

final class UserPointWorkflowsController extends Controller
{
    public function triggerWorkFlow(Request $request, EventService $eventService)
    {
        $participations = EventParticipation::where('event_id', 64)->get();

        foreach ($participations as $participation) {

            $eventService->userPointWorkflow($participation->user_id, $participation->event_id);
        }

    }
}
