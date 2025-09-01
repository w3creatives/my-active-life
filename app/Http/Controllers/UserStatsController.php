<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AmerithonPathDistance;
use App\Models\Event;
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
        private UserPointService $userPointService
    ) {}

    public function getUserStats(Request $request, string $type)
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

    public function userMonthlyPoints(Request $request): Collection
    {
        $eventId = (int) $request->query('event_id');
        $user = $request->user();

        return $this->userService->monthlies($eventId, $user);
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

        // Get route line data (sample of path points for the complete route)
        $routeLineData = AmerithonPathDistance::select('distance', 'coordinates')
            ->orderBy('distance')
            ->take(200) // Limit to 200 points for performance
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
}
