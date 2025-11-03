<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Models\Event;
use App\Services\MilestoneImageService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class FitLifeEventWebApis extends BaseController
{
    public function getQuests(Request $request, $type = 'quest'): JsonResponse
    {
        $user = Auth::user();

        $participation = $user->participations()->where(['event_id' => $user->preferred_event_id])->whereHas('event')->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        $pageLimit = $request->page_limit ?? 100;

        $activeTillDate = Carbon::now()->subDays(14)->format('Y-m-d');
        $currentDate = Carbon::now()->format('Y-m-d');

        $isJournal = ($type === 'journal');

        $isUpcoming = $request->list_type === 'upcoming';

        $cacheName = "quest_{$user->id}_{$isUpcoming}_{$request->event_id}_{$pageLimit}_{$request->is_archived}_{$currentDate}_{$activeTillDate}";

        // if(Cache::has($cacheName)){
        // $item = Cache::get($cacheName);
        // return $this->sendResponse($item, 'Response');
        // }

        $questRegistrations = $user->questRegistrations()
            ->where(function ($query) use ($request, $activeTillDate, $currentDate, $isJournal, $isUpcoming) {

                if ($isJournal) {
                    return $query;
                }

                if ($isUpcoming) {
                    return $query->where('archived', false)->where('date', '>=', $currentDate);
                }

                if ($request->is_archived) {
                    return $query->where('archived', true)->orWhere('date', '<', $activeTillDate);
                }

                return $query->where('archived', false)->where('date', '>=', $activeTillDate);
            })
            ->select(['id', 'date', 'notes', 'data', 'archived', 'shared', 'activity_id', 'created_at', 'updated_at', 'image'])
            ->with('activity', function ($query) {
                return $query->select(['id', 'sponsor', 'category', 'group', 'name', 'description', 'tags', 'total_points', 'social_hashtags', 'sports', 'available_from', 'available_until', 'data']);
            })
            ->whereHas('activity', function ($query) use ($event) {
                return $query->where('event_id', $event->id);
            })
            ->orderBy('date', $isJournal ? 'DESC' : 'ASC')
            ->simplePaginate($pageLimit)
            ->through(function ($item) use ($event) {
                if ($item->image) {
                    $item->image = url('uploads/quests/'.$item->image);
                }

                $activity = $item->activity;

                // $item->is_completed = $activity->milestones()->count() == $item->milestoneStatuses()->count();

                $milestone = $activity->milestones()->select(['id'])->where('total_points', '<=', 1000)->latest('total_points')->first();

                $hasCount = $item->milestoneStatuses()->where(['milestone_id' => $milestone ? $milestone->id : 0])->count();

                $item->is_completed = (bool) $hasCount;

                $activity->description = $this->htmlToPlainText($activity->description);

                $activity->bib_image = (new MilestoneImageService)->getBibImage($event->id, $activity->id, $item->is_completed);

                $item->activity = $activity;

                return $item;
            });

        // Cache::put($cacheName, $questRegistrations, now()->addHours(2));
        return $this->sendResponse($questRegistrations, 'Response');
    }

    public function getMileageByActivityType(Request $request): JsonResponse
    {
        $user = Auth::user();

        $participation = $user->participations()->where(['event_id' => $user->preferred_event_id])->whereHas('event')->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        // Get user points grouped by modality for the current event
        $userPoints = $user->points()
            ->where('event_id', $event->id)
            ->select('modality', DB::raw('SUM(amount) as total_miles'))
            ->groupBy('modality')
            ->get();

        // Calculate total miles
        $totalMiles = $userPoints->sum('total_miles');

        // Define colors for each modality
        $colors = [
            'run' => '#8884d8',
            'walk' => '#82ca9d',
            'bike' => '#ffc658',
            'swim' => '#ff7c7c',
            'other' => '#8dd1e1',
            'daily_steps' => '#d084d0',
        ];

        // Define display names for modalities
        $displayNames = [
            'run' => 'Running',
            'walk' => 'Walking',
            'bike' => 'Cycling',
            'swim' => 'Swimming',
            'other' => 'Other',
            'daily_steps' => 'Daily Steps',
        ];

        // Format data for the chart
        $chartData = [];
        foreach ($userPoints as $point) {
            $modality = $point->modality ?? 'other';
            $miles = (float) $point->total_miles;
            $percentage = $totalMiles > 0 ? ($miles / $totalMiles) * 100 : 0;

            $chartData[] = [
                'name' => $displayNames[$modality] ?? ucfirst($modality),
                'miles' => round($miles, 2),
                'percentage' => round($percentage, 1),
                'color' => $colors[$modality] ?? '#ffb347',
            ];
        }

        // Sort by miles descending
        usort($chartData, function ($a, $b) {
            return $b['miles'] <=> $a['miles'];
        });

        return $this->sendResponse([
            'data' => $chartData,
            'totalMiles' => round($totalMiles, 2),
        ], 'Activity type breakdown retrieved successfully');
    }

    public function getHeroismData(Request $request): JsonResponse
    {
        $user = Auth::user();

        $participation = $user->participations()->where(['event_id' => $user->preferred_event_id])->whereHas('event')->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        // Lifetime data (last 1 year)
        $lifetimeFrom = Carbon::now()->subYear()->format('Y-m-d');
        $lifetimeTo = Carbon::now()->format('Y-m-d');

        $lifetimeRegistrations = $user->questRegistrations()
            ->whereBetween('date', [$lifetimeFrom, $lifetimeTo])
            ->whereHas('activity', function ($query) use ($event) {
                return $query->where('event_id', $event->id);
            })
            ->count();

        // Get completed registrations for lifetime
        $lifetimeCompleted = DB::table('fit_life_activity_registrations as r')
            ->join('fit_life_activity_milestones as m', 'm.activity_id', '=', 'r.activity_id')
            ->join('fit_life_activity_milestone_statuses as s', function ($join) {
                $join->on('s.milestone_id', '=', 'm.id')
                    ->on('s.registration_id', '=', 'r.id');
            })
            ->where('r.user_id', $user->id)
            ->whereBetween('r.date', [$lifetimeFrom, $lifetimeTo])
            ->whereIn('r.activity_id', function ($query) use ($event) {
                $query->select('id')
                    ->from('fit_life_activities')
                    ->where('event_id', $event->id);
            })
            ->whereRaw('m.total_points = (SELECT MAX(total_points) FROM fit_life_activity_milestones WHERE activity_id = m.activity_id)')
            ->distinct()
            ->count('r.id');

        // Last 30 days data
        $last30DaysFrom = Carbon::now()->subDays(29)->format('Y-m-d');
        $last30DaysTo = Carbon::now()->format('Y-m-d');

        $last30DaysRegistrations = $user->questRegistrations()
            ->whereBetween('date', [$last30DaysFrom, $last30DaysTo])
            ->whereHas('activity', function ($query) use ($event) {
                return $query->where('event_id', $event->id);
            })
            ->count();

        // Get completed registrations for last 30 days
        $last30DaysCompleted = DB::table('fit_life_activity_registrations as r')
            ->join('fit_life_activity_milestones as m', 'm.activity_id', '=', 'r.activity_id')
            ->join('fit_life_activity_milestone_statuses as s', function ($join) {
                $join->on('s.milestone_id', '=', 'm.id')
                    ->on('s.registration_id', '=', 'r.id');
            })
            ->where('r.user_id', $user->id)
            ->whereBetween('r.date', [$last30DaysFrom, $last30DaysTo])
            ->whereIn('r.activity_id', function ($query) use ($event) {
                $query->select('id')
                    ->from('fit_life_activities')
                    ->where('event_id', $event->id);
            })
            ->whereRaw('m.total_points = (SELECT MAX(total_points) FROM fit_life_activity_milestones WHERE activity_id = m.activity_id)')
            ->distinct()
            ->count('r.id');

        // Calculate percentages
        $lifetimePercentage = $lifetimeRegistrations > 0
            ? round(($lifetimeCompleted / $lifetimeRegistrations) * 100)
            : 0;

        $last30DaysPercentage = $last30DaysRegistrations > 0
            ? round(($last30DaysCompleted / $last30DaysRegistrations) * 100)
            : 0;

        return $this->sendResponse([
            'lifetime' => [
                'total_registrations' => $lifetimeRegistrations,
                'total_completed' => $lifetimeCompleted,
                'completion_percentage' => $lifetimePercentage,
            ],
            'last_30_days' => [
                'total_registrations' => $last30DaysRegistrations,
                'total_completed' => $last30DaysCompleted,
                'completion_percentage' => $last30DaysPercentage,
            ],
        ], 'Heroism data retrieved successfully');
    }

    public function getFavoriteQuests(Request $request): JsonResponse
    {
        $user = Auth::user();

        $participation = $user->participations()->where(['event_id' => $user->preferred_event_id])->whereHas('event')->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        // Get current month start and end dates
        $monthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $monthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Query to get favorite activities (most registered) for current month
        $favoriteQuests = DB::table('fit_life_activity_registrations as r')
            ->join('fit_life_activities as a', 'a.id', '=', 'r.activity_id')
            ->select('a.name as activity_name', DB::raw('COUNT(DISTINCT r.id) as registration_count'))
            ->where('r.user_id', $user->id)
            ->where('a.event_id', $event->id)
            ->whereBetween('r.date', [$monthStart, $monthEnd])
            ->groupBy('r.activity_id', 'a.name')
            ->orderByDesc('registration_count')
            ->get();

        // Format data for pie chart
        $chartData = [];
        $totalRegistrations = $favoriteQuests->sum('registration_count');

        foreach ($favoriteQuests as $quest) {
            $percentage = $totalRegistrations > 0
                ? round(($quest->registration_count / $totalRegistrations) * 100, 1)
                : 0;

            $chartData[] = [
                'name' => $quest->activity_name,
                'value' => (int) $quest->registration_count,
                'percentage' => $percentage,
            ];
        }

        return $this->sendResponse([
            'data' => $chartData,
            'total_registrations' => $totalRegistrations,
        ], 'Favorite quests data retrieved successfully');
    }

    public function getQuestsCalendar(Request $request): JsonResponse
    {
        $user = Auth::user();

        $participation = $user->participations()->where(['event_id' => $user->preferred_event_id])->whereHas('event')->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        // Get year start and end
        $yearStart = Carbon::now()->startOfYear();
        $yearEnd = Carbon::now()->endOfYear();

        // Initialize activities array for all days of the year
        $activitiesOverYear = [];
        $currentDate = $yearStart->copy();

        while ($currentDate->lte($yearEnd)) {
            $weekIndex = $currentDate->weekOfYear % 53;
            $dayIndex = $currentDate->dayOfWeek === 0 ? 6 : ($currentDate->dayOfWeek - 1); // Monday = 0, Sunday = 6

            $activitiesOverYear[] = [
                'week' => $weekIndex,
                'day' => $dayIndex,
                'points' => 0,
                'date' => $currentDate->format('Y-m-d'),
                'activity_name' => null,
                'registration_id' => null,
            ];

            $currentDate->addDay();
        }

        // Get completed registrations for the year
        $completedRegistrations = DB::table('fit_life_activity_registrations as r')
            ->join('fit_life_activity_milestones as m', 'm.activity_id', '=', 'r.activity_id')
            ->join('fit_life_activity_milestone_statuses as s', function ($join) {
                $join->on('s.milestone_id', '=', 'm.id')
                    ->on('s.registration_id', '=', 'r.id');
            })
            ->join('fit_life_activities as a', 'a.id', '=', 'r.activity_id')
            ->select('r.id as registration_id', 'r.date', 'a.name as activity_name', 'a.total_points')
            ->where('r.user_id', $user->id)
            ->where('a.event_id', $event->id)
            ->whereBetween('r.date', [$yearStart->format('Y-m-d'), $yearEnd->format('Y-m-d')])
            ->whereRaw('m.total_points = (SELECT MAX(total_points) FROM fit_life_activity_milestones WHERE activity_id = m.activity_id)')
            ->orderBy('r.date', 'asc')
            ->get();

        // Update activities array with completed registrations
        foreach ($completedRegistrations as $registration) {
            $regDate = Carbon::parse($registration->date);
            $dayOfYear = $regDate->dayOfYear - 1; // 0-indexed

            if (isset($activitiesOverYear[$dayOfYear])) {
                $activitiesOverYear[$dayOfYear] = [
                    'week' => $activitiesOverYear[$dayOfYear]['week'],
                    'day' => $activitiesOverYear[$dayOfYear]['day'],
                    'points' => (int) $registration->total_points,
                    'date' => $registration->date,
                    'activity_name' => $registration->activity_name,
                    'registration_id' => (int) $registration->registration_id,
                ];
            }
        }

        return $this->sendResponse([
            'data' => $activitiesOverYear,
        ], 'Quests calendar data retrieved successfully');
    }

    private function htmlToPlainText($str): string
    {
        $str = str_replace('&nbsp;', ' ', $str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_COMPAT, 'UTF-8');
        $str = html_entity_decode($str, ENT_HTML5, 'UTF-8');
        $str = html_entity_decode($str);
        $str = htmlspecialchars_decode($str);
        $str = strip_tags($str);

        return preg_replace('~\h*(\R)\s*~', '$1', $str);
    }
}
