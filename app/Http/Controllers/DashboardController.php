<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * Display the dashboard page.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('dashboard');
    }

    /**
     * Get user points data for the calendar.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserPoints(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->input('date', now()->format('Y-m'));
        [$year, $month] = explode('-', $date);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // Get the current event (you may need to adjust this based on your application logic)
        $event = $user->participations()->with('event')->first()?->event;

        if (! $event) {
            return response()->json([
                'points' => [],
                'total' => 0,
            ]);
        }

        // Get points for the specified month
        $points = $user->points()
            ->selectRaw('SUM(amount) as total_mile, date, note')
            ->where('event_id', $event->id)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy(['date', 'note'])
            ->get()
            ->map(function ($item) use ($event, $user) {
                // Calculate cumulative miles
                $item->cumulative_mile = $user->points()
                    ->where('event_id', $event->id)
                    ->where('date', '<=', $item->date)
                    ->sum('amount');

                return [
                    'id' => uniqid(),
                    'date' => $item->date,
                    'miles' => $item->total_mile,
                    'cumulative_miles' => $item->cumulative_mile,
                    'note' => $item->note,
                ];
            });

        // Get total points for the event
        $totalPoints = $user->totalPoints()->where('event_id', $event->id)->first()?->amount ?? 0;

        return response()->json([
            'points' => $points,
            'total' => $totalPoints,
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
        ]);
    }

    /**
     * Get user statistics for the dashboard.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get the current event
        $event = $user->participations()->with('event')->first()?->event;

        if (! $event) {
            return response()->json([
                'stats' => [
                    'total_miles' => 0,
                    'best_day' => null,
                    'best_week' => null,
                    'best_month' => null,
                ],
            ]);
        }

        // Calculate best day
        $bestDay = $user->points()
            ->selectRaw('SUM(amount) as total, date')
            ->where('event_id', $event->id)
            ->groupBy('date')
            ->orderBy('total', 'desc')
            ->first();

        // Calculate best week
        $bestWeek = $user->points()
            ->selectRaw('SUM(amount) as total, YEARWEEK(date, 1) as yearweek')
            ->where('event_id', $event->id)
            ->groupBy('yearweek')
            ->orderBy('total', 'desc')
            ->first();

        // Calculate best month
        $bestMonth = $user->points()
            ->selectRaw('SUM(amount) as total, YEAR(date) as year, MONTH(date) as month')
            ->where('event_id', $event->id)
            ->groupBy(['year', 'month'])
            ->orderBy('total', 'desc')
            ->first();

        // Get total miles
        $totalMiles = $user->totalPoints()->where('event_id', $event->id)->first()?->amount ?? 0;

        return response()->json([
            'stats' => [
                'total_miles' => $totalMiles,
                'best_day' => $bestDay ? [
                    'date' => $bestDay->date,
                    'miles' => $bestDay->total,
                ] : null,
                'best_week' => $bestWeek ? [
                    'yearweek' => $bestWeek->yearweek,
                    'miles' => $bestWeek->total,
                ] : null,
                'best_month' => $bestMonth ? [
                    'year' => $bestMonth->year,
                    'month' => $bestMonth->month,
                    'miles' => $bestMonth->total,
                ] : null,
            ],
        ]);
    }

    /**
     * Add new points for the user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'miles' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'event_id' => 'required|exists:events,id',
        ]);

        $user = $request->user();

        // Check if user is participating in the event
        $participation = $user->participations()
            ->where('event_id', $validated['event_id'])
            ->first();

        if (! $participation) {
            return response()->json([
                'message' => 'You are not participating in this event.',
            ], 403);
        }

        // Create the point record
        $point = $user->points()->create([
            'event_id' => $validated['event_id'],
            'date' => $validated['date'],
            'amount' => $validated['miles'],
            'note' => $validated['note'] ?? null,
            'data_source_id' => 1, // Manual entry
        ]);

        return response()->json([
            'message' => 'Points added successfully.',
            'point' => $point,
        ]);
    }
}
