<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\DatasourcePointTracker;
use App\Models\Event;
use App\Models\EventParticipation;
use App\Models\User;
use App\Utilities\DataTable;
use Illuminate\Http\Request;

final class ReportsController extends Controller
{
    public function users()
    {
        $totalUserCount = User::query()->count();

        $totalUserParticipationCount = EventParticipation::query()->selectRaw('COUNT (DISTINCT  user_id)')
            ->first()->count;

        $totalUserParticipationActiveEventCount = EventParticipation::query()
            ->selectRaw('COUNT (DISTINCT  user_id)')
            ->whereHas('event', function ($query) {
                return $query->active();
            })
            ->first()->count;

        $totalUserParticipationInactiveEventCount = EventParticipation::query()
            ->selectRaw('COUNT (DISTINCT  user_id)')
            ->whereHas('event', function ($query) {
                return $query->inactive();
            })
            ->first()->count;

        $activeEventCount = Event::query()
            ->selectRaw('COUNT(id)')
            ->active()->first()->count;

        $inactiveEventCount = Event::query()
            ->selectRaw('COUNT(id)')
            ->inactive()->first()->count;

        $data = [
            'counters' => [
                ['title' => 'Users', 'value' => $totalUserCount, 'icon' => 'users', 'alertClass' => 'warning'],
                ['title' => 'Active Events', 'value' => $activeEventCount, 'icon' => 'shield', 'alertClass' => 'success'],
                ['title' => 'Inactive Events', 'value' => $inactiveEventCount, 'icon' => 'shield-lock', 'alertClass' => 'danger'],
                ['title' => 'User Participating in Events', 'value' => $totalUserParticipationCount, 'icon' => 'users-group', 'alertClass' => 'warning'],
                ['title' => 'User Participating in Active Events', 'value' => $totalUserParticipationActiveEventCount, 'icon' => 'users-group', 'alertClass' => 'success'],
                ['title' => 'User Participated in Inactive Events', 'value' => $totalUserParticipationInactiveEventCount, 'icon' => 'users-group', 'alertClass' => 'danger'],
            ],
            'tables' => [
                ['title' => 'Active Users By Data Sources', 'table' => 'data-source', 'id' => 'source-data-table'],
                ['title' => 'Active Events', 'table' => 'event', 'id' => 'active-event-table', 'type' => 'active'],
                ['title' => 'Inactive Events', 'table' => 'event', 'id' => 'inactive-event-table', 'type' => 'inactive'],
            ],
        ];

        // dd($activeEventCount, $inactiveEventCount, $userCount);

        return view('admin.reports.users', $data);
    }

    public function pointTracker(Request $request, DataTable $dataTable)
    {
        if ($request->ajax()) {

            $query = DatasourcePointTracker::query()
                ->selectRaw('datasource_point_trackers.*, data_sources.name as source')
                ->join('data_sources', 'data_sources.id', '=', 'datasource_point_trackers.data_source_id');


            [$itemCount, $items] = $dataTable->setSearchableColumns(['id', 'total_point','source'])->query($request, $query)->response();

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }
        return view('admin.reports.point-tracker');
    }

    public function events(Request $request, DataTable $dataTable)
    {
        if ($request->ajax()) {

            $type = $request->route()->parameter('type');

            $query = Event::query()
                ->selectRaw('id,name,start_date,end_date,event_type')
                ->withCount('participations')
                ->orderBy('id', 'asc')
                ->{$type}();

            [$itemCount, $items] = $dataTable->setSearchableColumns(['name', 'event_type'])->query($request, $query)->response();

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }
    }

    public function dataSources(Request $request, DataTable $dataTable)
    {
        if ($request->ajax()) {

            $query = DataSource::query()->exceptManual()->withCount('users');

            [$itemCount, $items] = $dataTable->setSearchableColumns(['name', 'description'])->query($request, $query)->response();

            $items = $items->map(function ($dataSource) {
                $dataSource->logo = view('admin.reports.tables.datasource-logo', compact('dataSource'))->render();
                $dataSource->users_count = User::whereHas('profiles', function ($query) use ($dataSource) {
                    return $query->where('data_source_id', $dataSource->id);
                })
                    ->whereHas('participations.event', function ($query) {
                        return $query->active();
                    })->count();

                return $dataSource;
            });


            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $items,
            ]);
        }
    }
}
