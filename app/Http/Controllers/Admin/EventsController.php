<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Event;
use App\Models\Modality;
use App\Services\EventService;
use App\Traits\RTEHelpers;
use App\Utilities\DataTable;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class EventsController extends Controller
{
    use RTEHelpers;

    public function index(Request $request, DataTable $dataTable, EventService $eventService)
    {

        if ($request->ajax()) {

            $query = Event::allowedTypes()->select(['id', 'name', 'event_type', 'start_date', 'open', 'bibs_name', 'event_group', 'logo', 'end_date', 'logo', 'email_template_id','event_color']);

            [$eventCount, $events] = $dataTable->setSearchableColumns(['name', 'event_type'])->query($request, $query)->response();

            $events = $events->map(function ($event) use ($eventService) {
                $event->name = view('admin.events.actions.title', compact('event'))->render();
                $event->event_type_text = $eventService->findEventType($event->event_type);
                $event->open = $event->open ? 'Open' : 'Closed';
                $event->bibs_name = $event->bibs_name ?? '--';
                $event->event_group = $event->event_group ?? '--';
                $event->email_template_name = $event->emailTemplate->name ?? '--';
                $event->action = [
                    view('admin.events.actions.event', compact('event'))->render(),
                ];

                return $event;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $eventCount,
                'recordsFiltered' => $eventCount,
                'data' => $events,
            ]);
        }

        return view('admin.events.list');
    }

    public function create(Request $request, EventService $eventService, $eventId = null)
    {

        $event = Event::find($eventId);

        $emailTemplates = EmailTemplate::query()->get();

        $selectedModalities = $this->decodeModalities($event->supported_modalities ?? 0);

        $eventTypes = collect($eventService->eventTypes())->except('race');
        $modalities = Modality::all();

        return view('admin.events.create', compact('eventTypes', 'modalities', 'event', 'selectedModalities', 'emailTemplates'));
    }

    public function store(Request $request, $eventId = null)
    {

        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'event_type' => 'required',
            'goals' => 'required',
            'social_hashtags' => 'required',
            'total_points' => 'required',
            'event_color' => 'required',
        ]);

        $data = $request->only('name', 'start_date', 'end_date', 'event_type', 'goals', 'social_hashtags', 'description', 'total_points', 'registration_url', 'bibs_name', 'event_group', 'future_start_message', 'event_color');
        $data['event_type'] = mb_strtolower($data['event_type']);
        $data['event_color'] = mb_strtoupper($data['event_color']);
        $data['registration_url'] = $data['registration_url'] ?? '#';
        $data['goals'] = json_encode(array_map('trim', explode(',', $data['goals'])));
        $data['start_date'] = Carbon::parse($data['start_date'])->format('Y-m-d');
        $data['end_date'] = Carbon::parse($data['end_date'])->format('Y-m-d');
        $data['supported_modalities'] = $this->encodeModalities($request->modalities ?? []);
        $data['open'] = $request->get('open_status');
        $data['email_template_id'] = $request->get('email_template_id', null);

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = 'event_'.time().'_'.uniqid().'.'.$logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/events', $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }

        $event = Event::find($eventId);

        if ($event) {
            $event->fill($data)->save();
            $message = 'Event updated successfully.';
        } else {
            $event = Event::create($data);
            $message = 'Event created successfully.';
        }

        [$routeName, $hasCount] = $event->event_misc_route;

        if (! $hasCount) {
            return redirect()->route($routeName, $event->id)->with('alert', ['type' => 'success', 'message' => $message]);

        }

        return redirect()->route('admin.events')->with('alert', ['type' => 'success', 'message' => $message]);
    }
}
