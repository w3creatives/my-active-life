<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventMilestone;
use App\Utilities\DataTable;
use Illuminate\Http\Request;

class MilestonesController extends Controller
{
    public function index(Request $request, DataTable $dataTable, $eventId)
    {
        if ($request->ajax()) {

            $query = EventMilestone::select(['id', 'name', 'distance', 'data', 'event_id'])
                ->where('event_id', $eventId);

            list($itemCount, $items) = $dataTable->setSearchableColumns(['name', 'distance'])->query($request, $query)->response();

            $items = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'distance' => $item->distance,
                    'data' => $item->video_url ?? '',
                    'logo' => view('admin.milestones.logo', compact('item'))->render(),
                    'action' => [
                        view('components.actions.milestone', compact('item'))->render(),
                    ],
                ];
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }

        $event = Event::type('regular')->find($eventId);

        if(!$event){
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => 'Invalid event.']);
        }

        return view('admin.milestones.list', compact('event'));
    }

    public function create(Request $request, $eventId, $milestoneId = null)
    {

        $event = Event::findOrFail($eventId);

        $eventMilestone = $event->milestones()->find($milestoneId);

        if ($request->ajax()) {
            return [
                'html' => view('admin.milestones.add', compact('event', 'eventMilestone'))->render()
            ];
        }

        return view('admin.milestones.create', compact('event', 'eventMilestone'));
    }

    public function store(Request $request, $eventId, $milestoneId = null)
    {

        $request->validate([
            'name' => 'required',
            'distance' => 'required|numeric',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'team_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $event = Event::findOrFail($eventId);

        $data = $request->only(['name', 'description', 'distance']);

        $videoData = $request->video_url ? ['flyover_url' => $request->video_url] : [];

        $data['data'] = json_encode($videoData);

        $eventMilestone = $event->milestones()->find($milestoneId);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = $event->id . '_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->move(public_path('uploads/milestones'), $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }
        if ($request->hasFile('team_logo')) {
            $teamLogoFile = $request->file('team_logo');
            $teamLogoFileName = $event->id . '_' . time() . '_' . uniqid() . '.' . $teamLogoFile->getClientOriginalExtension();
            $teamLogoFile->move(public_path('uploads/milestones'), $teamLogoFileName, 'public');

            $data['team_logo'] = $teamLogoFileName;
        }

        if ($eventMilestone) {
            $eventMilestone->fill($data)->save();
            return redirect()->route('admin.events.milestones', $event->id)->with('alert', ['type' => 'success', 'message' => 'Milestone updated successfully.']);
        }

        $event->milestones()->create($data);

        return redirect()->route('admin.events.milestones', $event->id)->with('alert', ['type' => 'success', 'message' => 'Milestone created successfully.']);
    }

    public function view(Request $request, $eventId, $milestoneId)
    {
        $event = Event::findOrFail($eventId);

        $eventMilestone = $event->milestones()->find($milestoneId);

        return ['html' => view('admin.milestones.view', compact('eventMilestone'))->render()];
    }
}
