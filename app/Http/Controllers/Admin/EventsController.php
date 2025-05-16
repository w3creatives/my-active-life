<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
class EventsController extends Controller
{
    public function index(Request $request)
    {

        if($request->ajax()){

            $searchTerm = $request->input('search.value');

            $events = Event::select(['id','name','start_date','end_date','logo'])
            ->where(function($query) use ($searchTerm) {

                if($searchTerm){
                    $query->where('name','ILIKE',"%{$searchTerm}%");
                }

                return $query;
            });

            $eventCount = $events->count();

            $events = $events->limit($request->get('limit', 10))
                ->skip($request->get('offset', 0))
                ->get();

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $eventCount,
                'recordsFiltered' => $eventCount,
                'data' => $events
            ]);

        }

        return view('admin.events.list');
    }
}
