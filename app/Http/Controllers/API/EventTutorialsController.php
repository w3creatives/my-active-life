<?php

namespace App\Http\Controllers\API;

use App\Models\Event;
use App\Models\EventTutorial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventTutorialsController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);
        
        $tutorials = EventTutorial::where('event_id', $request->event_id)
            ->get()
            ->map(function ($tutorial) {
                return [
                    'event_id' => $tutorial->event_id,
                    'tutorial_text' => $tutorial->tutorial_text
                ];
            });
        
        return $this->sendResponse($tutorials, 'Tutorials retrieved successfully');
    }
    
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'tutorial_text' => 'required|string',
        ]);

        $tutorial = EventTutorial::create($request->only(['event_id', 'tutorial_text']));

        return $this->sendResponse($tutorial, 'Tutorial created successfully');
    }
    
    public function update(Request $request, EventTutorial $tutorial): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'tutorial_text' => 'required|string',
        ]);

        $eventTutorial = EventTutorial::where('event_id', $request->event_id)->first();

        if (!$eventTutorial) {
            return $this->sendError('Tutorial not found for this event', [], 404);
        }

        $eventTutorial->update([
            'tutorial_text' => $request->tutorial_text
        ]);

        return $this->sendResponse([
            'event_id' => $eventTutorial->event_id,
            'tutorial_text' => $eventTutorial->tutorial_text
        ], 'Tutorial updated successfully');
    }
    
    public function destroy(Request $request, EventTutorial $tutorial): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
        ]);

        $eventTutorial = EventTutorial::where('event_id', $request->event_id)->first();

        if (!$eventTutorial) {
            return $this->sendError('Tutorial not found for this event', [], 404);
        }

        $eventTutorial->delete();

        return $this->sendResponse([], 'Tutorial deleted successfully');
    }
    
    public function getAllTutorials(): JsonResponse
    {
        $tutorials = EventTutorial::all()
            ->map(function ($tutorial) {
                return [
                    'event_id' => $tutorial->event_id,
                    'tutorial_text' => $tutorial->tutorial_text
                ];
            });
        
        return $this->sendResponse($tutorials, 'All tutorials retrieved successfully');
    }
}