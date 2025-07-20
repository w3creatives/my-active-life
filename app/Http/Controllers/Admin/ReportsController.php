<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\Event;
use App\Models\EventParticipation;
use App\Models\User;

final class ReportsController extends Controller
{
    public function users()
    {

        $dataSources = DataSource::query()->exceptManual()->withCount('users')->get();

        $dataSources = $dataSources->map(function ($dataSource) {
            $dataSource->users_count = User::whereHas('profiles', function ($query) use ($dataSource) {
                return $query->where('data_source_id', $dataSource->id);
            })
            ->whereHas('participations.event', function ($query) {
                return $query->active();
            })->count();

            return $dataSource;
        });

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

        $activeEvents = Event::query()
            ->selectRaw('id,name,start_date,end_date,event_type')
            ->withCount('participations')
            ->orderBy('id', 'asc')
            ->active()->get();

        $inactiveEvents = Event::query()
            ->selectRaw('id,name,start_date,end_date,event_type')
            ->withCount('participations')
            ->orderBy('id', 'asc')
            ->inactive()->get();

        $data = [
            'counters' => [
                ['title' => 'Users', 'value' => $totalUserCount, 'icon' => 'users', 'alertClass' => 'warning'],
                ['title' => 'Active Events', 'value' => $activeEvents->count(), 'icon' => 'shield', 'alertClass' => 'success'],
                ['title' => 'Inactive Events', 'value' => $inactiveEvents->count(), 'icon' => 'shield-lock', 'alertClass' => 'danger'],
                ['title' => 'User Participating in Events', 'value' => $totalUserParticipationCount, 'icon' => 'users-group', 'alertClass' => 'warning'],
                ['title' => 'User Participating in Active Events', 'value' => $totalUserParticipationActiveEventCount, 'icon' => 'users-group', 'alertClass' => 'success'],
                ['title' => 'User Participated in Inactive Events', 'value' => $totalUserParticipationInactiveEventCount, 'icon' => 'users-group', 'alertClass' => 'danger'],
            ],
            'tables' => [
                ['title' => 'Active Users By Data Sources', 'items' => $dataSources, 'table' => 'data-source'],
                ['title' => 'Active Events', 'items' => $activeEvents, 'table' => 'event'],
                ['title' => 'Inactive Events', 'items' => $inactiveEvents, 'table' => 'event'],
            ],
        ];

        // dd($activeEventCount, $inactiveEventCount, $userCount);

        return view('admin.reports.users', $data);
    }
}
