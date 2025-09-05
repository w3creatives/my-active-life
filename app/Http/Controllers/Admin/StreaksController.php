<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
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

        $event = Event::promotional()->findOrFail($eventId);

        $eventStreak = $event->streaks()->find($request->route()->parameter('streakId'));

        $emailTemplates = EmailTemplate::query()->get();

        $selectedEmailTemplate = ($eventStreak && $eventStreak->email_template_id) ? $eventStreak->email_template_id : $event->email_template_id;

        return view('admin.streaks.create', compact('event', 'eventStreak', 'emailTemplates', 'selectedEmailTemplate'));
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
        $data['email_template_id'] = $request->get('email_template_id', null);

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = $event->id.'_'.time().'_'.uniqid().'.'.$logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/streaks', $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }

        if ($request->hasFile('calendar_logo')) {
            $teamLogoFile = $request->file('calendar_logo');
            $teamLogoFileName = $event->id.'_'.time().'_'.uniqid().'.'.$teamLogoFile->getClientOriginalExtension();
            $teamLogoFile->storeAs('uploads/streaks', $teamLogoFileName, 'public');
            $data['calendar_logo'] = $teamLogoFileName;
        }

        if ($request->hasFile('bib_image')) {
            $teamLogoFile = $request->file('bib_image');
            $teamLogoFileName = 'individual-bib-'.$event->id.'_'.time().'_'.uniqid().$request->min_distance.'.'.$teamLogoFile->getClientOriginalExtension();
            $teamLogoFile->storeAs('uploads/streaks/bibs', $teamLogoFileName, 'public');
            $data['bib_image'] = $teamLogoFileName;
        }

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
