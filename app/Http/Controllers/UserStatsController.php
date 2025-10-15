<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AmerithonPathDistance;
use App\Models\Event;
use App\Models\EventMilestone;
use App\Models\Team;
use App\Services\TeamService;
use App\Services\UserPointService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final readonly class UserStatsController
{
    public function __construct(
        private UserService $userService,
        private UserPointService $userPointService,
        private TeamService $teamService
    ) {}

    public function getUserStats(Request $request, TeamService $teamService, string $type)
    {
        if ($type === 'last30days') {
            return $this->userLast30DaysStats($request);
        }

        if ($type === 'monthlies') {
            return $this->userMonthlyPoints($request);
        }

        if ($type === 'yearly') {
            return $this->userYearlyPoints($request);
        }

        if ($type === 'amerithon-map') {
            return $this->userAmerithonMapData($request);
        }

        if ($type === 'target') {
            return $this->userTargetData($request);
        }

        if ($type === 'progress') {
            return $this->progressStats($request, $teamService);
        }

        if ($type === 'next-milestone') {
            return $this->nextMilestone($request, $teamService);
        }

        return false;
    }

    public function userLast30DaysStats(Request $request)
    {
        $user = $request->user();
        $eventId = (int) $request->query('event_id');

        $user = $request->user();
        $eventId = (int) $request->query('event_id');

        $points = $this->userPointService->last30DaysStats($user, $eventId);

        return $points['stats']->map(function ($item) {
            $item['label'] = Carbon::parse($item['date'])->format('d M y'); // e.g., 18 Dec 25

            return $item;
        });
    }

    public function userMonthlyPoints(Request $request): array
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $startOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfMonth()->format('Y-m-d');

        $startOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfWeek()->format('Y-m-d');

        $today = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->format('Y-m-d');

        $pointStat = $this->userService->currentAchievements($eventId, [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek], $user);

        $data = $this->userService->monthlies($eventId, $user);

        return compact('data','pointStat');

    }

    public function userYearlyPoints(Request $request): Collection
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        return $this->userService->yearly($eventId, $user);
    }

    public function userTotalPoints(Request $request): int
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        return $this->userService->total($eventId, $user);
    }

    public function userAmerithonMapData(Request $request): JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $userId = (int) $request->query('user_id');
        $user = $request->user();

        // Get user's total distance for the event
        $totalDistance = $this->userService->total($eventId, $user);

        // Get the maximum distance for completion percentage
        $maxDistance = AmerithonPathDistance::max('distance') ?: 3000; // Default to ~3000 miles for cross-country
        $completionPercentage = $maxDistance > 0 ? ($totalDistance / $maxDistance) * 100 : 0;

        // Get route line data - sample evenly across the entire route for better visualization
        $totalCount = AmerithonPathDistance::count();
        $sampleSize = 2000; // Increased sample size for smoother route line
        $step = max(1, (int) ($totalCount / $sampleSize));

        $routeLineData = AmerithonPathDistance::select('distance', 'coordinates')
            ->whereRaw('MOD(id, ?) = 0', [$step]) // Sample every Nth record
            ->orderBy('distance')
            ->get()
            ->map(function ($point) {
                $coordinates = is_string($point->coordinates)
                    ? json_decode($point->coordinates, true)
                    : $point->coordinates;

                return [
                    'lat' => $coordinates['lat'] ?? $coordinates['latitude'] ?? 0,
                    'lng' => $coordinates['lng'] ?? $coordinates['longitude'] ?? 0,
                    'distance' => $point->distance,
                ];
            })
            ->filter(function ($point) {
                return $point['lat'] !== 0 && $point['lng'] !== 0;
            })
            ->values()
            ->toArray();

        // Find the nearest position on the Amerithon path for user's current location
        $userPosition = null;
        if ($totalDistance > 0) {
            $pathData = AmerithonPathDistance::select('id', 'distance', 'coordinates')
                ->orderByRaw('ABS(distance - ?)', [$totalDistance])
                ->first();

            if ($pathData) {
                $coordinates = is_string($pathData->coordinates)
                    ? json_decode($pathData->coordinates, true)
                    : $pathData->coordinates;

                $userPosition = [
                    'latitude' => $coordinates['lat'] ?? $coordinates['latitude'] ?? 0,
                    'longitude' => $coordinates['lng'] ?? $coordinates['longitude'] ?? 0,
                    'distance_covered' => $totalDistance,
                    'user_id' => $userId,
                    'user_name' => $user->display_name ?? ($user->first_name.' '.$user->last_name),
                ];
            }
        }

        return response()->json([
            'user_position' => $userPosition,
            'total_distance' => $maxDistance,
            'completion_percentage' => round($completionPercentage, 2),
            'route_line' => $routeLineData,
            'message' => $totalDistance <= 0 ? 'No distance recorded yet' : null,
        ]);
    }

    public function getUserAchievements(Request $request): JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $startOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfMonth()->format('Y-m-d');

        $startOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfWeek()->format('Y-m-d');

        $today = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->format('Y-m-d');

        $cacheName = "user_achievement_{$user->id}_user_{$eventId}_{$startOfMonth}_{$endOfMonth}_{$startOfWeek}_{$endOfWeek}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);

            return response()->json(['data' => $item]);
        }

        $event = Event::find($eventId);

        if (! $event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        [$achievementData, $totalPoints, $yearwisePoints] = $this->userService->achievements($event, [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek], $user);

        $data = [
            'achievement' => $achievementData,
            'miles' => [
                'total' => $totalPoints,
                'chart' => $yearwisePoints,
            ],
            'event' => $event,
        ];

        Cache::put($cacheName, $data, now()->addHours(2));

        return response()->json(['data' => $data]);
    }

    public function getTeamStats(Request $request, TeamService $teamService, string $type)
    {
        if ($type === 'last30days') {
            return $this->teamLast30DaysStats($request);
        }

        if ($type === 'monthlies') {
            return $this->teamMonthlyPoints($request);
        }

        if ($type === 'yearly') {
            return $this->teamYearlyPoints($request);
        }

        if ($type === 'amerithon-map') {
            return $this->teamAmerithonMapData($request);
        }

        if ($type === 'target') {
            return $this->teamTargetData($request);
        }

        if ($type === 'progress') {
            return $this->progressStats($request, $teamService, 'team');
        }

        if ($type === 'next-milestone') {
            return $this->nextMilestone($request, $teamService, 'team');
        }

        return false;
    }

    public function teamLast30DaysStats(Request $request)
    {
        $user = $request->user();
        $eventId = (int) $request->query('event_id');

        $team = $this->teamService->getUserTeam($user, $eventId);
        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        $points = $this->teamService->last30DaysStats($team, $eventId);

        return $points['stats']->map(function ($item) {
            $item['label'] = Carbon::parse($item['date'])->format('d M y'); // e.g., 18 Dec 25
            // Ensure compatibility with frontend - rename amount to daily_total
            $item['daily_total'] = $item['amount'];
            unset($item['amount']);

            return $item;
        });
    }

    public function teamMonthlyPoints(Request $request)
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $team = $this->teamService->getUserTeam($user, $eventId);
        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        $startOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfMonth()->format('Y-m-d');

        $startOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfWeek()->format('Y-m-d');

        $today = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->format('Y-m-d');

        $pointStat = $this->teamService->currentAchievements($eventId, [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek], $team);

        $data = $this->teamService->monthlies($eventId, $team);
        return compact('data','pointStat');
    }

    public function teamYearlyPoints(Request $request): Collection|JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $team = $this->teamService->getUserTeam($user, $eventId);
        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        // For now, return team yearly points (this could be enhanced later if needed)
        return collect([]);
    }

    public function teamTotalPoints(Request $request): int|JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $team = $this->teamService->getUserTeam($user, $eventId);
        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        return $this->teamService->total($eventId, $team);
    }

    public function teamAmerithonMapData(Request $request): JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $team = $this->teamService->getUserTeam($user, $eventId);
        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        // Get team's total distance for the event
        $totalDistance = $this->teamService->total($eventId, $team);

        // Get the maximum distance for completion percentage
        $maxDistance = AmerithonPathDistance::max('distance') ?: 3000; // Default to ~3000 miles for cross-country
        $completionPercentage = $maxDistance > 0 ? ($totalDistance / $maxDistance) * 100 : 0;

        // Get route line data - sample evenly across the entire route for better visualization
        $totalCount = AmerithonPathDistance::count();
        $sampleSize = 2000; // Increased sample size for smoother route line
        $step = max(1, (int) ($totalCount / $sampleSize));

        $routeLineData = AmerithonPathDistance::select('distance', 'coordinates')
            ->whereRaw('MOD(id, ?) = 0', [$step]) // Sample every Nth record
            ->orderBy('distance')
            ->get()
            ->map(function ($point) {
                $coordinates = is_string($point->coordinates)
                    ? json_decode($point->coordinates, true)
                    : $point->coordinates;

                return [
                    'lat' => $coordinates['lat'] ?? $coordinates['latitude'] ?? 0,
                    'lng' => $coordinates['lng'] ?? $coordinates['longitude'] ?? 0,
                    'distance' => $point->distance,
                ];
            })
            ->filter(function ($point) {
                return $point['lat'] !== 0 && $point['lng'] !== 0;
            })
            ->values()
            ->toArray();

        // Find the nearest position on the Amerithon path for team's current location
        $teamPosition = null;
        if ($totalDistance > 0) {
            $pathData = AmerithonPathDistance::select('id', 'distance', 'coordinates')
                ->orderByRaw('ABS(distance - ?)', [$totalDistance])
                ->first();

            if ($pathData) {
                $coordinates = is_string($pathData->coordinates)
                    ? json_decode($pathData->coordinates, true)
                    : $pathData->coordinates;

                $teamPosition = [
                    'latitude' => $coordinates['lat'] ?? $coordinates['latitude'] ?? 0,
                    'longitude' => $coordinates['lng'] ?? $coordinates['longitude'] ?? 0,
                    'distance_covered' => $totalDistance,
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                ];
            }
        }

        return response()->json([
            'team_position' => $teamPosition,
            'total_distance' => $maxDistance,
            'completion_percentage' => round($completionPercentage, 2),
            'route_line' => $routeLineData,
            'message' => $totalDistance <= 0 ? 'No distance recorded yet for team' : null,
        ]);
    }

    public function getTeamAchievements(Request $request): JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $team = $this->teamService->getUserTeam($user, $eventId);
        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        $startOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfMonth()->format('Y-m-d');

        $startOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfWeek()->format('Y-m-d');

        $today = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->format('Y-m-d');

        $cacheName = "team_achievement_{$team->id}_event_{$eventId}_{$startOfMonth}_{$endOfMonth}_{$startOfWeek}_{$endOfWeek}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);

            return response()->json(['data' => $item]);
        }

        $event = Event::find($eventId);
        if (! $event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        [$achievementData, $totalPoints, $yearwisePoints] = $this->teamService->achievements($event, [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek], $team);

        $teamAchievement = $this->teamService->teamAchievement($team);

        $data = [
            'achievement' => $teamAchievement,
            'miles' => [
                'total' => $totalPoints,
                'chart' => $yearwisePoints,
            ],
            'event' => $event,
        ];

        Cache::put($cacheName, $data, now()->addHours(2));

        return response()->json(['data' => $data]);
    }

    public function userTargetData(Request $request): JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $event = Event::find($eventId);
        if (! $event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        // Get user's current distance
        $currentDistance = $this->userService->total($eventId, $user);

        // Parse event goals - use user's personal goal if set, otherwise use event goals
        $userSettings = $user->settings ?? [];
        $rtyGoals = $userSettings['rty_goals'] ?? [];
        $personalGoal = null;

        // Find user's personal goal for this event
        foreach ($rtyGoals as $goalSet) {
            if (isset($goalSet[$event->name]) || isset($goalSet[mb_strtolower(str_replace(' ', '_', $event->name))])) {
                $personalGoal = $goalSet[$event->name] ?? $goalSet[mb_strtolower(str_replace(' ', '_', $event->name))];
                break;
            }
        }

        // Use personal goal or fallback to event goals
        if ($personalGoal) {
            $targetGoal = (float) $personalGoal;
        } else {
            $goals = $event->goals ? json_decode($event->goals, true) : [];
            $goals = is_array($goals) ? array_map('floatval', $goals) : [];
            sort($goals);
            $targetGoal = ! empty($goals) ? end($goals) : 1000; // Default 1000 miles
        }

        // Calculate timing based on mobile API logic
        $eventStartDate = Carbon::parse($event->start_date);
        $eventEndDate = Carbon::parse($event->end_date);
        $now = Carbon::now();

        $totalDays = $eventStartDate->diffInDays($eventEndDate);
        $daysSinceStart = max(1, $eventStartDate->diffInDays($now));
        $daysRemaining = max(1, $now->diffInDays($eventEndDate));

        // Calculate required daily average for the entire event
        $dailyTargetMileage = $targetGoal / $totalDays;

        // Calculate what they should have by now (on-target mileage)
        $onTargetMileage = $daysSinceStart * $dailyTargetMileage;

        // Calculate percentage (how they're doing vs where they should be)
        $onTargetPercentage = $onTargetMileage > 0 ? ($currentDistance / $onTargetMileage) * 100 : 0;

        // Calculate current daily average and needed daily average
        $currentDailyAverage = $daysSinceStart > 0 ? $currentDistance / $daysSinceStart : 0;
        $neededDailyAverage = $daysRemaining > 0 ? ($targetGoal - $currentDistance) / $daysRemaining : 0;

        // Get goal message and indicator using mobile API logic
        $goalMessage = $this->getGoalMessage('default', $onTargetPercentage, $eventEndDate->format('M j, Y'));

        // Calculate estimated completion date
        $estimatedDaysToComplete = $currentDailyAverage > 0 ? ($targetGoal - $currentDistance) / $currentDailyAverage : 999;
        $estimatedCompletionDate = $now->copy()->addDays($estimatedDaysToComplete);

        return response()->json([
            'current_distance' => $currentDistance,
            'target_goal' => $targetGoal,
            'on_target_mileage' => (float)($onTargetMileage),
            'on_target_percentage' => (float)($onTargetPercentage),
            'days_remaining' => $daysRemaining,
            'daily_average_needed' => (float)($neededDailyAverage),
            'current_daily_average' => (float)($currentDailyAverage),
            'is_on_track' => $onTargetPercentage >= 100,
            'goal_indicator' => $goalMessage['indicator'] ?? '',
            'goal_message' => $goalMessage['message'] ?? '',
            'event_end_date' => $event->end_date,
            'estimated_completion_date' => $estimatedCompletionDate->format('Y-m-d'),
        ]);
    }

    public function teamTargetData(Request $request): JsonResponse
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        $team = $this->teamService->getUserTeam($user, $eventId);
        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        $event = Event::find($eventId);
        if (! $event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        // Get team's current distance
        $currentDistance = $this->teamService->total($eventId, $team);

        // For teams, use event goals (highest goal as target)
        $goals = $event->goals ? json_decode($event->goals, true) : [];
        $goals = is_array($goals) ? array_map('floatval', $goals) : [];
        sort($goals);
        $targetGoal = ! empty($goals) ? end($goals) : 1000; // Default 1000 miles

        // Calculate timing based on mobile API logic
        $eventStartDate = Carbon::parse($event->start_date);
        $eventEndDate = Carbon::parse($event->end_date);
        $now = Carbon::now();

        $totalDays = $eventStartDate->diffInDays($eventEndDate);
        $daysSinceStart = max(1, $eventStartDate->diffInDays($now));
        $daysRemaining = max(1, $now->diffInDays($eventEndDate));

        // Calculate required daily average for the entire event
        $dailyTargetMileage = $targetGoal / $totalDays;

        // Calculate what they should have by now (on-target mileage)
        $onTargetMileage = $daysSinceStart * $dailyTargetMileage;

        // Calculate percentage (how they're doing vs where they should be)
        $onTargetPercentage = $onTargetMileage > 0 ? ($currentDistance / $onTargetMileage) * 100 : 0;

        // Calculate current daily average and needed daily average
        $currentDailyAverage = $daysSinceStart > 0 ? $currentDistance / $daysSinceStart : 0;
        $neededDailyAverage = $daysRemaining > 0 ? ($targetGoal - $currentDistance) / $daysRemaining : 0;

        // Get goal message and indicator using mobile API logic
        $goalMessage = $this->getGoalMessage('default', $onTargetPercentage, $eventEndDate->format('M j, Y'));

        // Calculate estimated completion date
        $estimatedDaysToComplete = $currentDailyAverage > 0 ? ($targetGoal - $currentDistance) / $currentDailyAverage : 999;
        $estimatedCompletionDate = $now->copy()->addDays($estimatedDaysToComplete);

        return response()->json([
            'current_distance' => $currentDistance,
            'target_goal' => $targetGoal,
            'on_target_mileage' => (float)($onTargetMileage),
            'on_target_percentage' => (float)($onTargetPercentage),
            'days_remaining' => $daysRemaining,
            'daily_average_needed' => (float)($neededDailyAverage),
            'current_daily_average' => (float)($currentDailyAverage),
            'is_on_track' => $onTargetPercentage >= 100,
            'goal_indicator' => $goalMessage['indicator'] ?? '',
            'goal_message' => $goalMessage['message'] ?? '',
            'event_end_date' => $event->end_date,
            'estimated_completion_date' => $estimatedCompletionDate->format('Y-m-d'),
        ]);
    }

    public function progressStats(Request $request, $teamService, $type = 'you'): JsonResponse
    {
        $user = $request->user();

        // Get current event
        $currentEvent = null;
        if ($user->preferred_event_id) {
            $currentEvent = Event::find($user->preferred_event_id);
        }

        if (! $currentEvent) {
            return response()->json([]);
        }

        $totalDistance = $currentEvent->total_points;

        $userGoal = null;
        if ($type === 'team') {

            $team = $teamService->userEventTeam($user, $currentEvent);

            $coveredTotalDistance = $team->totalPoints()->where('event_id', $currentEvent->id)
                ->sum('amount');
            $chutzpahFactorUnit = $teamService->chutzpahFactor($team);

            $totalDistance = $chutzpahFactorUnit * $currentEvent->total_points;

        } else {
            // Get user's goal for this event
            $userSettings = json_decode($user->settings, true) ?? [];
            $rtyGoals = $userSettings['rty_goals'] ?? [];
            $eventSlug = mb_strtolower(str_replace(' ', '-', $currentEvent->name));

            foreach ($rtyGoals as $goal) {
                if (isset($goal[$eventSlug])) {
                    $userGoal = (float) $goal[$eventSlug];
                    break;
                }
            }

            $totalDistance = $userGoal ?? $currentEvent->total_points;

            $coveredTotalDistance = $user->totalPoints()->where('event_id', $currentEvent->id)
                ->sum('amount');
        }

        $totalDistance = (float) $totalDistance;
        $coveredDistance = (float) $coveredTotalDistance;

        $percentage = (float) (($coveredDistance / $totalDistance) * 100);
        $remainingDistance = ($totalDistance - $coveredDistance);
        $remainingDistance = (max($remainingDistance, 0));
        $isCompleted = $coveredDistance >= $totalDistance;

        return response()->json([
            'eventName' => $currentEvent->name,
            'totalDistance' => $totalDistance,
            'coveredDistance' => $coveredDistance,
            'percentage' => $percentage,
            'remainingDistance' => $remainingDistance,
            'isCompleted' => $isCompleted,
            'userGoal' => $userGoal,
            'goalPercentage' => $percentage,
        ]);
    }

    public function nextMilestone(Request $request, $teamService, $type = 'you'): JsonResponse
    {
        $user = $request->user();

        // Get current event
        $currentEvent = null;
        if ($user->preferred_event_id) {
            $currentEvent = Event::find($user->preferred_event_id);
        }

        if (! $currentEvent) {
            return response()->json([]);
        }

        $totalDistance = $currentEvent->total_points;

        $userGoal = null;
        if ($type === 'team') {
            $team = $teamService->userEventTeam($user, $currentEvent);
            $coveredTotalDistance = $team->totalPoints()->where('event_id', $currentEvent->id)
                ->sum('amount');
        } else {
            $coveredTotalDistance = $user->totalPoints()->where('event_id', $currentEvent->id)
                ->sum('amount');
        }

        $totalDistance = (float) $totalDistance;

        // Find next milestone
        $nextMilestone = EventMilestone::where('event_id', $currentEvent->id)
            ->where('distance', '>', $coveredTotalDistance)
            ->orderBy('distance')
            ->first();

        // Find previous milestone for progress calculation
        $previousMilestone = EventMilestone::where('event_id', $currentEvent->id)
            ->where('distance', '<=', $coveredTotalDistance)
            ->orderBy('distance', 'desc')
            ->first();

        $data = [
            'coveredDistance' => $coveredTotalDistance,
            'previousMilestoneDistance' => $previousMilestone ? (float) $previousMilestone->distance : 0,
            'eventName' => $currentEvent->name,
            'milestone' => [],
            'distanceToGo' => 0,
            'segmentProgress' => 0,
            'progress' => 100,
            'segmentProgress' => 100,
        ];

        if ($nextMilestone) {

            $distanceToGo = $nextMilestone->distance - $coveredTotalDistance;

            $segmentStart = $data['previousMilestoneDistance'];
            $segmentTotal = $nextMilestone->distance - $segmentStart;
            $segmentCovered = $coveredTotalDistance - $segmentStart;
            $segmentProgress = $segmentTotal > 0 ? ($segmentCovered / $segmentTotal) * 100 : 100;

            $data['milestone']['id'] = $nextMilestone->id;
            $data['milestone']['name'] = $nextMilestone->name;
            $data['milestone']['distance'] = (float) $nextMilestone->distance;
            $data['milestone']['description'] = $nextMilestone->description;
            $data['milestone']['logo'] = $nextMilestone->logo;
            $data['milestone']['data'] = json_decode($nextMilestone->data, true);
            $data['distanceToGo'] = $distanceToGo;
            $data['segmentProgress'] = $segmentProgress;
            $data['progress'] = ($coveredTotalDistance / $nextMilestone->distance) * 100;
        }

        return response()->json($data);
    }

    private function getGoalMessage($attitude, $onTargetPercentage, $expectedFinishDate): array
    {
        $goalMessage = [];

        if ($onTargetPercentage < 80) {
            $goalMessage['indicator'] = 'behind';
            $goalMessage['message'] = 'You can do this! To meet your current goal you just need to get rolling! Of course, you can also change your goal in Account Settings.';
        } elseif ($onTargetPercentage >= 80 && $onTargetPercentage < 100) {
            $goalMessage['indicator'] = 'nearly there';
            $goalMessage['message'] = "We believe in you! Every day is a new day. Start fresh and set your sights high to get back on track! You're so close!";
        } elseif ($onTargetPercentage >= 100 && $onTargetPercentage < 120) {
            $goalMessage['indicator'] = 'on target';
            $goalMessage['message'] = "WHOA! You're crushing this challenge! Keep it up, you're on pace to finish on {$expectedFinishDate} and not a day later!";
        } else { // >= 120
            $goalMessage['indicator'] = 'ahead';
            $goalMessage['message'] = "Wow! You are amazing and well ahead of your goal! You are on target to finish approximately on {$expectedFinishDate}.";
        }

        return $goalMessage;
    }

    public function teamMemberStats(Request $request): JsonResponse
    {
        $user = $request->user();

       $event = $user->preferredEvent;

        $team = $this->teamService->getUserTeam($user, $event->id);

        if (! $team) {
            return response()->json(['error' => 'User is not part of any team'], 404);
        }

        $data = $this->teamService->memberAchievements($event, $user, $team);

        return response()->json($data);
    }
}
