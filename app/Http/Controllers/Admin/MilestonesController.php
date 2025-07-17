<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Event;
use App\Models\EventMilestone;
use App\Utilities\DataTable;
use Illuminate\Http\Request;

class MilestonesController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {
        $eventId = $request->route()->parameter('id');
        $activityId = $request->route()->parameter('activityId');

        $event = Event::find($eventId);

        $activity = $event->fitActivities()->find($activityId);

        if ($request->ajax()) {

            if (in_array($event->event_type, ['regular', 'month'])) {
                $query = $event->milestones()->select(['id', 'name', 'distance', 'data', 'event_id', 'logo', 'team_logo']);
                $searchableColumns = ['name', 'distance'];
            } else {
                $query = $activity->milestones()->select(['id', 'name', 'total_points', 'data', 'activity_id']);
                $searchableColumns = ['name', 'total_points'];
            }

            list($itemCount, $items) = $dataTable->setSearchableColumns($searchableColumns)->query($request, $query)->response();

            $items = $items->map(function ($item) use ($event) {

                $data = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'distance' => isset($item->distance) ? $item->distance : $item->total_points,
                    'data' => $item->video_url ?? '',
                    'action' => [
                        view('admin.milestones.actions.milestone', compact('item', 'event'))->render(),
                    ],
                ];

                if ($event->event_type == 'regular') {
                    $data['logo'] = view('admin.milestones.logo', compact('item'))->render();
                } else {
                    $data['logo'] = 'N/A';
                }

                return $data;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }

        return view('admin.milestones.list', compact('event', 'activity'));
    }

    public function create(Request $request)
    {

        $eventId = $request->route()->parameter('id');
        $activityId = $request->route()->parameter('activityId');
        $milestoneId = $request->route()->parameter('milestoneId');

        $event = Event::findOrFail($eventId);

        $isRegularEvent = in_array($event->event_type, ['regular', 'month']);

        if ($isRegularEvent) {
            $eventMilestone = $event->milestones()->find($milestoneId);
        } else {
            $activity = $event->fitActivities()->find($activityId);
            $eventMilestone = $activity->milestones()->find($milestoneId);
            if ($eventMilestone) {
                $eventMilestone->distance = $eventMilestone->total_points ?? null;
            }
        }

        if ($request->ajax()) {
            return [
                'html' => view('admin.milestones.add', compact('event', 'eventMilestone'))->render()
            ];
        }

        if ($isRegularEvent) {
            $backUrl = route('admin.events.milestones', $event->id);
        } else {
            $backUrl = route('admin.events.activity.milestones', [$event->id, $activityId]);
        }

        $emailTemplates = EmailTemplate::query()->get();

        $selectedEmailTemplate = ($eventMilestone && $eventMilestone->email_template_id) ? $eventMilestone->email_template_id : $event->email_template_id;

        return view('admin.milestones.create', compact('event', 'eventMilestone', 'isRegularEvent', 'backUrl', 'emailTemplates', 'selectedEmailTemplate'));
    }

    public function store(Request $request)
    {
        $eventId = $request->route()->parameter('id');
        $milestoneId = $request->route()->parameter('milestoneId');

        $event = Event::findOrFail($eventId);

        $request->validate([
            'name' => 'required',
            'distance' => 'required|numeric',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'team_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->only(['name', 'distance', 'description']);

        $videoData = $request->video_url ? ['flyover_url' => $request->video_url] : [];

        $data['data'] = json_encode($videoData);

        $data['email_template_id'] = $request->get('email_template_id', null);

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = $event->id . '_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/milestones', $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }
        if ($request->hasFile('bw_logo')) {
            $logoFile = $request->file('bw_logo');
            $logoFileName = $event->id . '_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/milestones', $logoFileName, 'public');
            $data['bw_logo'] = $logoFileName;
        }
        if ($request->hasFile('calendar_logo')) {
            $logoFile = $request->file('calendar_logo');
            $logoFileName = $event->id . '_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/milestones', $logoFileName, 'public');
            $data['calendar_logo'] = $logoFileName;
        }
        if ($request->hasFile('bw_calendar_logo')) {
            $logoFile = $request->file('bw_calendar_logo');
            $logoFileName = $event->id . '_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/milestones', $logoFileName, 'public');
            $data['bw_calendar_logo'] = $logoFileName;
        }

        if ($request->hasFile('team_logo')) {
            $teamLogoFile = $request->file('team_logo');
            $teamLogoFileName = $event->id . '_' . time() . '_' . uniqid() . '.' . $teamLogoFile->getClientOriginalExtension();
            $teamLogoFile->storeAs('uploads/milestones', $teamLogoFileName, 'public');
            $data['team_logo'] = $teamLogoFileName;
        }

        if (in_array($event->event_type, ['regular', 'month'])) {

            $eventMilestone = $event->milestones()->find($milestoneId);

            if ($eventMilestone) {
                $eventMilestone->fill($data)->save();
                return redirect()->route('admin.events.milestones', $event->id)->with('alert', ['type' => 'success', 'message' => 'Milestone updated successfully.']);
            }

            $event->milestones()->create($data);

            return redirect()->route('admin.events.milestones', $event->id)->with('alert', ['type' => 'success', 'message' => 'Milestone created successfully.']);
        }

        if ($event->event_type === 'fit_life') {
            $data['total_points'] = $data['distance'];
            unset($data['distance']);

            $activityId = $request->route()->parameter('activityId');
            $activity = $event->fitActivities()->find($activityId);
            $eventMilestone = $activity->milestones()->find($milestoneId);

            $flashMessage = 'Milestone created successfully.';

            if ($eventMilestone) {
                $eventMilestone->fill($data)->save();
                $flashMessage = 'Milestone updated successfully.';
            }

            $activity->milestones()->create($data);

            return redirect()
                ->route('admin.events.activity.milestones', [$event->id, $activityId])
                ->with('alert', ['type' => 'success', 'message' => $flashMessage]);
        }
    }

    public function view(Request $request, $eventId, $milestoneId)
    {
        $event = Event::findOrFail($eventId);

        $eventMilestone = $event->milestones()->find($milestoneId);

        return ['html' => view('admin.milestones.view', compact('eventMilestone'))->render()];
    }
}
