<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserPointService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

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
}
