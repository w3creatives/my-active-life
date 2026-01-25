<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Utilities\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TeamsController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {
        if ($request->ajax()) {

            $query = Team::query()
                ->select([
                    'users.first_name',
                    'users.last_name',
                    'users.display_name',
                    'teams.*',
                    DB::raw('events.name as event_name'),
                ])
                ->join('events', 'teams.event_id', '=', 'events.id')
                ->join('users', 'teams.owner_id', '=', 'users.id');

            [$userCount, $items] = $dataTable->setSearchableColumns([
                'teams.name',
                'teams.public_profile',
                'users.first_name',
                'users.last_name',
                'users.display_name',
                'events.name',
            ])
                ->query($request, $query)->response();

            $items = $items->map(function ($item) {

                $item->public_profile = $item->public_profile ? 'Public' : 'Private';
                $item->owner_name = $item->owner->full_name;
                $item->event_name = $item->event->name;
                $item->created = $item->created_at->toDateTimeString();

                return $item;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $userCount,
                'recordsFiltered' => $userCount,
                'data' => $items,
            ]);

        }

        $columns = [
            ['title' => 'ID', 'name' => 'id', 'data' => 'id'],
            ['title' => 'Name', 'name' => 'name', 'data' => 'name'],
            ['title' => 'Event Name', 'name' => 'event_name', 'data' => 'event_name'],
            ['title' => 'Team Owner', 'name' => 'first_name', 'data' => 'owner_name'],
            ['title' => 'Public Profile', 'name' => 'public_profile', 'data' => 'public_profile'],
            ['title' => 'Created Date', 'name' => 'created_at', 'data' => 'created'],
        ];

        return view('admin.teams.list', compact('columns'));
    }
}
