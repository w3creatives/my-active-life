<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EventTutorials\GetEventTutorials;
use App\Actions\Follow\UndoFollowing;
use App\Models\Event;
use App\Services\EventService;
use App\Services\MailboxerService;
use App\Services\TeamService;
use App\Services\UserService;
use App\Traits\RTEHelpers;
use App\Traits\UserPointFetcher;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    use RTEHelpers, UserPointFetcher;

    /**
     * Display the dashboard page.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard');
    }

    public function stats(): Response
    {
        return Inertia::render('stats');
    }

    public function tutorials(): Response
    {
        $tutorials = GetEventTutorials::run(['event_id' => 64]);

        return Inertia::render('tutorials', [
            'tutorials' => $tutorials,
        ]);
    }

    public function follow(Request $request, UserService $userService, TeamService $teamService, EventService $eventService): Response
    {
        $user = auth()->user();
        $userSearchTerm = $request->input('searchUser', '');
        $perPageUser = (int) request('perPageUser', 5);
        $teamSearchTerm = $request->input('searchTeam', '');
        $perPageTeam = (int) request('perPageTeam', 5);

        $teamFollowings = $teamService->following($user, 64, 5, 'web')->toArray();
        $userFollowings = $userService->followings($user, 64, 5, 'web')->toArray();
        $users = $eventService->userParticipations($user, 64, $userSearchTerm, $perPageUser, 'web')->toArray();
        $teams = $teamService->all($user, 64, $teamSearchTerm, 'all', $perPageTeam, 'web')->toArray();

        return Inertia::render('follow/index', [
            'teamFollowings' => $teamFollowings,
            'userFollowings' => $userFollowings,
            'users' => $users,
            'teams' => $teams,
            'filters' => array_filter([
                'searchUser' => $userSearchTerm ?: null,
                'perPageUser' => $perPageUser !== 5 ? $perPageUser : null, // default is 5
                'searchTeam' => $teamSearchTerm ?: null,
                'perPageTeam' => $perPageTeam !== 5 ? $perPageTeam : null, // default is 5
            ]),
        ]);
    }

    /**
     * Get user points data for the calendar.
     */
    public function getUserPoints(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->input('date', now()->format('Y-m'));
        [$year, $month] = explode('-', $date);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        $event = Event::get()->where('id', 64)->first();
        $eventId = $event->id;
        $eventName = $event->name;

        $pointsCacheKey = "user_dashboard_points_{$user->id}_{$startDate}_to_{$endDate}_for_{$eventId}";
        Cache::forget($pointsCacheKey);

        $points = Cache::remember($pointsCacheKey, now()->addMinutes(15), function () use ($user, $startDate, $endDate, $eventId) {
            $data = $this->fetchUserPointsInDateRange($user, $startDate, $endDate, $eventId);

            return $data['points']->toArray();
        });

        $totalPointsCacheKey = "user_event_total_points_{$user->id}_{$startDate}_to_{$endDate}_for_{$eventId}";
        Cache::forget($totalPointsCacheKey);

        // Get total points for the event
        $totalPoints = Cache::remember($totalPointsCacheKey, now()->addMinutes(15), function () use ($user, $eventId) {
            return $this->fetchUserEventTotalPoints($user, $eventId);
        });

        return response()->json([
            'points' => $points,
            'total' => $totalPoints,
            'event' => [
                'id' => $eventId,
                'name' => $eventName,
            ],
        ]);
    }

    public function getUserDailyPoints(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->input('date', now()->format('Y-m-d'));

        $eventId = 64;

        $pointsCacheKey = "user_daily_points_{$user->id}_{$date}_{$eventId}";
        Cache::forget($pointsCacheKey);

        $dailyPoints = Cache::remember($pointsCacheKey, now()->addMinutes(15), function () use ($user, $date, $eventId) {
            return $this->fetchUserDailyPoints($user, $date, $eventId, true);
        });

        return response()->json([
            'points' => $dailyPoints,
        ]);
    }

    /**
     * Get user statistics for the dashboard.
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

    public function conversations(MailboxerService $mailbox)
    {
        $user = Auth::user();
        $conversations = $mailbox->getConversations($user)->toJson();

        return Inertia::render('conversations', [
            'conversations' => $conversations,
        ]);
    }

    public function newConversation()
    {
        return Inertia::render('new-conversation');
    }

    /**
     * Temporarily set the user's selected event (session-based, not persisted)
     */
    public function selectTempEvent(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        // Store the selected event ID in the session
        session(['selected_event_id' => $request->event_id]);

        // Return to the previous page with updated props
        return redirect()->back();
    }

    public function unfollow(Request $request, UndoFollowing $undoFollowing)
    {
        $result = (new UndoFollowing())($request, $request->user());

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }
}
