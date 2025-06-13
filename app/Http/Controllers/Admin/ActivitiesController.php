<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FitLifeActivity;
use App\Utilities\DataTable;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class ActivitiesController extends Controller
{
    public function index(Request $request, DataTable $dataTable, $eventId)
    {
        if ($request->ajax()) {

            $query = FitLifeActivity::select(['id', 'sponsor', 'category', 'group', 'name', 'tags', 'social_hashtags', 'available_from', 'available_until', 'event_id'])
                ->where('event_id', $eventId);

            list($itemCount, $items) = $dataTable->setSearchableColumns(['name', '$item'])->query($request, $query)->response();

            $items = $items->map(function ($item) {
                $item->action = [
                    view('admin.activities.actions.activity', compact('item'))->render(),
                ];
                return $item;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }
        $event = Event::type('fit_life')->find($eventId);

        if (!$event) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => 'Invalid event.']);
        }
        return view('admin.activities.list', compact('event'));
    }

    public function create(Request $request, $eventId, $activityId = null)
    {

        $activity = FitLifeActivity::where('event_id', $eventId)->where('id', $activityId)->first();

        $sports = [];

        if($activity){
            $sports = explode(',',str_replace(['{','}'],'',$activity->sports));
        }

        return view('admin.activities.create', compact('eventId', 'activityId', 'activity','sports'));

    }

    public  function store(Request $request, $eventId, $activityId = null){

        $request->validate([
            'sponsor' => 'required',
            'category' => 'required',
            'group' => 'required',
            'name' => 'required',
            'description' => 'required',
            'total_points' => 'required|numeric',
            'available_from' => 'required|date',
            'available_until' => 'required|date|after:available_from',
            'tags' => 'required',
            'social_hashtags' => 'required',
            'sports' => 'required|array|min:1',
        ]);

        $data = $request->only([
            'sponsor', 'category', 'group', 'name',
            'total_points', 'available_from', 'available_until',
            'tags', 'social_hashtags', 'sports',
        ]);

        $data['description'] = json_encode(['description' => $request->get('description'),'about_title' => $request->get('about_title'), 'about_description' => $request->get('about_description')]);

        $data['available_from'] = Carbon::parse($data['available_from'])->format('Y-m-d');
        $data['available_until'] = Carbon::parse($data['available_until'])->format('Y-m-d');
        $data['sports'] = sprintf('{%s}',implode(',',array_map('trim',$data['sports'])));

        $data['data'] = json_encode(['prize' => ['url' => $request->get('prize_url'), 'description' => $request->get('prize_description')]]);

        $event = Event::find($eventId);

        $activity = $event->fitActivities()->find($activityId);

        if ($activity) {
            $activity->fill($data)->save();
            return redirect()->route('admin.events.activities', $eventId)->with('alert', ['type' => 'success', 'message' => 'Activity updated.']);
        }

        $event->fitActivities()->create($data);
        return redirect()->route('admin.events.activities', $eventId)->with('alert', ['type' => 'success', 'message' => 'Activity created.']);
    }

    public function destroy(Request $request, $eventId, $activityId){

        $event = Event::find($eventId);

        $activity = $event->fitActivities()->find($activityId);

        if(!$activity){
            return redirect()->route('admin.events.activities', $eventId)->with('alert', ['type' => 'danger', 'message' => 'Invalid activity.']);
        }

        $activity->delete();

        return redirect()->route('admin.events.activities', $eventId)->with('alert', ['type' => 'success', 'message' => 'Activity deleted.']);
    }
}
