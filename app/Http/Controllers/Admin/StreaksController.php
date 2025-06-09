<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Utilities\DataTable;
use Exception;
use Illuminate\Http\Request;

final class StreaksController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {

        $eventId = $request->route()->parameter('id');

        $event = Event::promotional()->findOrFail($eventId);

        if ($request->ajax()) {

            $query = $event->streaks();

            [$itemCount, $items] = $dataTable->setSearchableColumns(['name', 'days_count'])->query($request, $query)->response();

            $items = $items->map(function ($item) use ($event) {

                $data = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'days_count' => $item->days_count,
                    'min_distance' => $item->min_distance ?? '',
                    'action' => [
                        view('admin.streaks.actions.streak', compact('item', 'event'))->render(),
                    ],
                ];

                return $data;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }

        return view('admin.streaks.list', compact('event'));
    }

    public function create(Request $request)
    {

        $eventId = $request->route()->parameter('id');

        $event = Event::find($eventId);

        $eventStreak = $event->streaks()->find($request->route()->parameter('streakId'));

        return view('admin.streaks.create', compact('event', 'eventStreak'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'days_count' => 'required|numeric',
            'min_distance' => 'required:digits',
        ]);

        $eventId = $request->route()->parameter('id');
        $streakId = $request->route()->parameter('streakId');

        $event = Event::promotional()->findOrFail($eventId);

        $streak = $event->streaks()->find($streakId);

        $data = $request->only(['name', 'days_count']);

        $data['data'] = json_encode(['min_distance' => $request->min_distance]);

        if ($streak) {
            $streak->fill($data)->save();

            return redirect()->route('admin.events.streaks', $eventId)->with('alert', ['type' => 'success', 'message' => 'Streak updated successfully.']);
        }

        $event->streaks()->create($data);

        return redirect()->route('admin.events.streaks', $eventId)->with('alert', ['type' => 'success', 'message' => 'Streak created successfully.']);
    }

    public function destroy(Request $request)
    {

        $eventId = $request->route()->parameter('id');

        $streakId = $request->route()->parameter('streakId');

        try {
            $event = Event::promotional()->findOrFail($eventId);

            $streak = $event->streaks()->findOrFail($streakId);

            $streak->delete();

        } catch (Exception $e) {
            return redirect()->route('admin.events.streaks', $eventId)->with('alert', ['type' => 'danger', 'message' => 'Unable to delete streak.']);
        }

        return redirect()->route('admin.events.streaks', $eventId)->with('alert', ['type' => 'success', 'message' => 'Streak deleted successfully.']);
    }
}
