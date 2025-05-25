<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use App\Utilities\DataTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\{
    Event,
    Modality
};

class EventsController extends Controller
{
    public function index(Request $request, DataTable $dataTable, EventService $eventService)
    {

        if ($request->ajax()) {

            $query = Event::select(['id', 'name', 'event_type', 'start_date', 'end_date', 'logo']);

            list($eventCount, $events) = $dataTable->setSearchableColumns(['name', 'event_type'])->query($request, $query)->response();

            $events = $events->map(function ($event) use ($eventService) {
                $event->event_type = $eventService->findEventType($event->event_type);
                $event->action = [
                    view('components.actions.event', compact('event'))->render()
                ];
                return $event;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $eventCount,
                'recordsFiltered' => $eventCount,
                'data' => $events
            ]);
        }

        return view('admin.events.list');
    }

    public function create(Request $request, EventService $eventService)
    {

        $eventTypes = $eventService->eventTypes();
        $modalities = Modality::all();

        return view('admin.events.create', compact('eventTypes', 'modalities'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'event_type' => 'required',
            'goals' => 'required',
        ]);

        $data = $request->only('name', 'start_date', 'end_date', 'event_type', 'goals', 'social_hashtags', 'description', 'total_points', 'registration_url');
        $data['event_type'] = strtolower($data['event_type']);
        $data['registration_url'] = $data['registration_url'] ?? '#';
        $data['goals'] = json_encode(array_map('trim', explode(',', $data['goals'])));
        $data['start_date'] = Carbon::parse($data['start_date'])->format('Y-m-d');
        $data['end_date'] = Carbon::parse($data['end_date'])->format('Y-m-d');

        Event::create($data);
        return redirect()->route('admin.events')->with('alert', ['type' => 'success', 'message' => 'Event created successfully.']);
    }


}
