<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Utilities\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class EventTutorialsController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {
        $eventId = $request->route()->parameter('eventId');

        $event = Event::findOrFail($eventId);

        if ($request->ajax()) {
            $query = $event->tutorials()->query();
            [$itemCount, $items] = $dataTable->setSearchableColumns(['name', 'event_type'])->query($request, $query)->response();

            $events = $items->map(function ($item) {
                // $event->name = view('admin.events.actions.title', compact('event'))->render();

                $item->action = [
                    // view('admin.events.actions.event', compact('event'))->render(),
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

        return view('admin.events.tutorials.list', compact('event'));
    }

    public function create(Request $request)
    {

        $event = Event::findOrFail($request->route()->parameter('eventId'));

        $tutorialTypes = ['heading', 'text', 'video'];

        $eventTutorial = $event->tutorials()->first();

        $tutorials = $eventTutorial->content ?? collect([]);

        return view('admin.events.tutorials.create', compact('event', 'tutorialTypes', 'tutorials', 'eventTutorial'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'type.*' => 'required|in:heading,text,video',
            'content.*' => 'required_if:type.*,heading,required_if:type.*,text',
            'level.*' => 'required_if:type.*,heading',
            'source.*' => 'required_if:type.*,video',
            'thumb.*' => 'required_if:type.*,video',
            'title.*' => 'required_if:type.*,video',
            'url.*' => 'required_if:type.*,video',
        ], [
            'type.*.required' => 'The type field is required.',
            'type.*.in' => 'The type must be one of heading, text, or video.',

            'content.*.required_if' => 'Content is required when type is heading or text.',
            'level.*.required_if' => 'Level is required when type is heading.',

            'source.*.required_if' => 'Source is required when type is video.',
            'thumb.*.required_if' => 'Thumbnail is required when type is video.',
            'title.*.required_if' => 'Title is required when type is video.',
            'url.*.required_if' => 'URL is required when type is video.',
        ]);

        $event = Event::findOrFail($request->route()->parameter('eventId'));

        $eventTutorial = $event->tutorials()->first();

        $items = [];

        $tutorials = $request->except('_token');

        if (! $tutorials) {
            $event->tutorials()->delete();
            return redirect()->route('admin.events.tutorials', $event->id)->with('alert', ['type' => 'success', 'message' => 'Event Tutorial updated successfully']);
        }

        foreach ($request->get('type') as $key => $type) {

            $data = ['type' => $type];

            switch ($type) {
                case 'heading':
                    $data['content'] = $request->input('content.'.$key);
                    $data['level'] = $request->input('level.'.$key);
                    break;
                case 'text':
                    $data['content'] = $request->input('content.'.$key);
                    break;
                case 'video':
                    $data['source'] = $request->input('source.'.$key);
                    $data['thumb'] = $request->input('thumb.'.$key);
                    $data['title'] = $request->input('title.'.$key);
                    $data['url'] = $request->input('url.'.$key);
                    break;
            }

            $items[] = $data;
        }

        if ($eventTutorial) {
            $eventTutorial->fill(['tutorial_text' => json_encode($items)])->save();

            return redirect()->route('admin.events.tutorials', $event->id)->with('alert', ['type' => 'success', 'message' => 'Event Tutorial updated successfully']);
        }

        $event->tutorials()->create(['tutorial_text' => json_encode($items)]);

        return redirect()->route('admin.events.tutorials', $event->id)->with('alert', ['type' => 'success', 'message' => 'Event Tutorial created successfully']);
    }

    public function destroy(Request $request)
    {
        $event = Event::findOrFail($request->route()->parameter('eventId'));

        $event->tutorials()->where(['id' => $request->route()->parameter('id')])->delete();

        return redirect()->route('admin.events.tutorials', $event->id)->with('alert', ['type' => 'success', 'message' => 'Event Tutorial deleted successfully']);
    }
}
