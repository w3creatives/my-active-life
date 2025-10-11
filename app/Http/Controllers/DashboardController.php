<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EventTutorials\GetEventTutorials;
use App\Actions\Follow\RequestFollow;
use App\Actions\Follow\UndoFollowing;
use App\Models\DataSource;
use App\Models\Event;
use App\Models\EventMilestone;
use App\Models\Team;
use App\Repositories\UserPointRepository;
use App\Repositories\UserRepository;
use App\Services\EventService;
use App\Services\MailboxerService;
use App\Services\TeamService;
use App\Services\UserService;
use App\Traits\RTEHelpers;
use App\Traits\UserEventParticipationTrait;
use App\Traits\UserPointFetcher;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    use RTEHelpers, UserEventParticipationTrait, UserPointFetcher;

    /**
     * Display the dashboard page.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard');
    }

    public function stats(): Response
    {
        $user = auth()->user();

        // Get current event
        $currentEvent = null;
        if ($user->preferred_event_id) {
            $currentEvent = Event::find($user->preferred_event_id);
        }

        if (! $currentEvent) {
            return redirect()->route('preferred.event');
        }


        return Inertia::render('stats/index');
    }

    public function tutorials(): Response
    {
        return Inertia::render('tutorials');
    }

    /**
     * Get tutorials data for progressive loading.
     */
    public function getTutorialsData(Request $request): JsonResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id ?? $user->event_participations()->first()?->event_id;

        $tutorials = GetEventTutorials::run(['event_id' => $eventId]);

        return response()->json([
            'tutorials' => $tutorials,
        ]);
    }

    public function trophyCase(Request $request): Response
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id ?? $user->event_participations()->first()?->event_id;

        if (! $eventId) {
            return Inertia::render('trophy-case/index', [
                'trophyData' => null,
                'error' => 'No event participation found',
            ]);
        }

        $event = Event::find($eventId);
        $eventMilestonesService = new \App\Services\EventMilestones();

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        // Get milestones with completion status
        $milestonesData = $eventMilestonesService->getEventMilestonesWithStatus($eventId, $user->id);

        // Get user achievements data for personal bests
        $userService = new UserService(new UserRepository, new UserPointRepository);

        $startOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfMonth()->format('Y-m-d');
        $startOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->endOfWeek()->format('Y-m-d');
        $today = Carbon::now()->setTimezone($user->time_zone_name ?? 'UTC')->format('Y-m-d');

        [$achievementData, $totalPoints, $yearwisePoints] = $userService->achievements($event, [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek], $user);

        $trophyData = [
            'event' => $event,
            'milestones' => $milestonesData['status'] ? $milestonesData['milestones'] : [],
            'achievements' => $achievementData,
            'total_distance' => $totalPoints,
            'user_distance' => $user->totalPoints()->where('event_id', $eventId)->sum('amount'),
            'team' => $team,
        ];

        return Inertia::render('trophy-case/index', [
            'trophyData' => $trophyData,
        ]);
    }

    public function teams(): Response
    {
        return Inertia::render('teams/index');
    }

    public function follow(Request $request): Response
    {
        $filters = array_filter([
            'searchUser' => $request->input('searchUser') ?: null,
            'perPageUser' => $request->input('perPageUser', 5) !== 5 ? (int) $request->input('perPageUser', 5) : null,
            'searchTeam' => $request->input('searchTeam') ?: null,
            'perPageTeam' => $request->input('perPageTeam', 5) !== 5 ? (int) $request->input('perPageTeam', 5) : null,
        ]);

        return Inertia::render('follow/index', [
            'filters' => $filters,
        ]);
    }

    public function preferredEvent(): Response
    {
        $user = Auth::user();

        $participations = $this->userParticipations($user);
        $events = $participations->map(function ($participation) use ($user) {
            $event = $participation->event;

            return [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'logo_url' => $event->logo_url,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'event_type' => $event->event_type,
                'is_past' => $event->isPastEvent(),
                'is_preferred' => $user->preferred_event_id === $event->id,
                'participation_id' => $participation->id,
            ];
        });

        return Inertia::render('preferred-event/index', [
            'events' => $events,
        ]);
    }

    /**
     * Get user followings data.
     */
    public function getUserFollowings(UserService $userService): JsonResponse
    {
        $user = auth()->user();
        $userFollowings = $userService->followings($user, 64, 5, 'web')->toArray();

        return response()->json([
            'userFollowings' => $userFollowings,
        ]);
    }

    /**
     * Get team followings data.
     */
    public function getTeamFollowings(TeamService $teamService): JsonResponse
    {
        $user = auth()->user();
        $teamFollowings = $teamService->following($user, 64, 5, 'web')->toArray();

        return response()->json([
            'teamFollowings' => $teamFollowings,
        ]);
    }

    /**
     * Get available users data.
     */
    public function getAvailableUsers(Request $request, EventService $eventService): JsonResponse
    {
        $user = auth()->user();
        $userSearchTerm = $request->input('searchUser', '');
        $perPageUser = (int) $request->input('perPageUser', 5);
        $page = (int) $request->input('page', 1);

        // Set the current page for Laravel pagination
        request()->merge(['usersPage' => $page]);

        $users = $eventService->searchUserParticipationList($user, 64, $userSearchTerm, $perPageUser, 'web')->toArray();

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Get available teams data.
     */
    public function getAvailableTeams(Request $request, TeamService $teamService): JsonResponse
    {
        $user = auth()->user();
        $teamSearchTerm = $request->input('searchTeam', '');
        $perPageTeam = (int) $request->input('perPageTeam', 5);
        $page = (int) $request->input('page', 1);

        // Set the current page for Laravel pagination
        request()->merge(['teamsPage' => $page]);

        $teams = $teamService->all($user, 64, $teamSearchTerm, 'all', $perPageTeam, 'web')->toArray();

        return response()->json([
            'teams' => $teams,
        ]);
    }

    /**
     * Get basic user statistics (fast loading).
     */
    public function getBasicStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get the current event
        $event = $user->participations()->with('event')->first()?->event;

        if (! $event) {
            return response()->json([
                'stats' => [
                    'total_miles' => 0,
                ],
            ]);
        }

        // Get total miles (this should be fast)
        $totalMiles = $user->totalPoints()->where('event_id', $event->id)->first()?->amount ?? 0;

        return response()->json([
            'stats' => [
                'total_miles' => $totalMiles,
            ],
        ]);
    }

    /**
     * Get achievement statistics (slower loading).
     */
    public function getAchievements(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get the current event
        $event = $user->participations()->with('event')->first()?->event;

        if (! $event) {
            return response()->json([
                'achievements' => [
                    'best_day' => null,
                    'best_week' => null,
                    'best_month' => null,
                ],
            ]);
        }

        // Calculate best day (potentially slower query)
        $bestDay = $user->points()
            ->selectRaw('SUM(amount) as total, date')
            ->where('event_id', $event->id)
            ->groupBy('date')
            ->orderBy('total', 'desc')
            ->first();

        // Calculate best week (potentially slower query)
        $bestWeek = $user->points()
            ->selectRaw('SUM(amount) as total, YEARWEEK(date, 1) as yearweek')
            ->where('event_id', $event->id)
            ->groupBy('yearweek')
            ->orderBy('total', 'desc')
            ->first();

        // Calculate best month (potentially slower query)
        $bestMonth = $user->points()
            ->selectRaw('SUM(amount) as total, YEAR(date) as year, MONTH(date) as month')
            ->where('event_id', $event->id)
            ->groupBy(['year', 'month'])
            ->orderBy('total', 'desc')
            ->first();

        return response()->json([
            'achievements' => [
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
     * Get user points data for the calendar.
     */
    public function getUserPoints(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->input('date', now()->format('Y-m'));
        [$year, $month] = explode('-', $date);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // Get user's preferred event or first participating event
        $eventId = $user->preferred_event_id ?? $user->participations()->first()?->event_id;
        if (! $eventId) {
            return response()->json([
                'points' => [],
                'total' => 0,
                'event' => null,
            ]);
        }

        $event = Event::find($eventId);
        if (! $event) {
            return response()->json([
                'points' => [],
                'total' => 0,
                'event' => null,
            ]);
        }

        $eventName = $event->name;

        $pointsCacheKey = "user_dashboard_points_{$user->id}_{$startDate}_to_{$endDate}_for_{$eventId}";
        Cache::forget($pointsCacheKey);

        $pointsWithMilestones = Cache::remember($pointsCacheKey, now()->addMinutes(15), function () use ($user, $startDate, $endDate, $eventId, $event) {
            $data = $this->fetchUserPointsInDateRange($user, $startDate, $endDate, $eventId);
            $points = $data['points'];

            // Calculate cumulative miles and check for milestones
            $pointsArray = [];
            $cumulativeMiles = 0;

            // Get all milestones for this event
            $milestones = $event->milestones()->orderBy('distance')->get();

            foreach ($points as $point) {
                $pointDate = $point->date;
                $dailyMiles = $point->amount;

                // Get cumulative miles up to this date
                $cumulativeMiles = $user->points()
                    ->where('event_id', $eventId)
                    ->where('date', '>=', $event->start_date)
                    ->where('date', '<=', $pointDate)
                    ->sum('amount');

                // Get previous day's cumulative miles to check if milestone was crossed on this date
                $previousDate = Carbon::parse($pointDate)->subDay()->format('Y-m-d');
                $previousCumulativeMiles = $user->points()
                    ->where('event_id', $eventId)
                    ->where('date', '>=', $event->start_date)
                    ->where('date', '<', $pointDate)
                    ->sum('amount');

                // Find milestone earned on this specific date
                $milestoneEarned = null;
                foreach ($milestones as $milestone) {
                    if ($cumulativeMiles >= $milestone->distance && $previousCumulativeMiles < $milestone->distance) {
                        $milestoneEarned = [
                            'id' => $milestone->id,
                            'name' => $milestone->name,
                            'distance' => $milestone->distance,
                            'description' => $milestone->description,
                            'calendar_logo_url' => $milestone->calendar_logo,
                            'calendar_team_logo_url' => $milestone->calendar_team_logo,
                            'bib_image_url' => $milestone->bib_image,
                            'team_bib_image_url' => $milestone->team_bib_image,
                        ];
                        break; // Only show the first milestone earned on this date
                    }
                }

                $pointsArray[] = [
                    'id' => $point->id,
                    'date' => $point->date,
                    'amount' => $point->amount,
                    'cumulative_miles' => $cumulativeMiles,
                    'milestone' => $milestoneEarned,
                ];
            }

            return $pointsArray;
        });

        $totalPointsCacheKey = "user_event_total_points_{$user->id}_{$startDate}_to_{$endDate}_for_{$eventId}";
        Cache::forget($totalPointsCacheKey);

        // Get total points for the event
        $totalPoints = Cache::remember($totalPointsCacheKey, now()->addMinutes(15), function () use ($user, $eventId) {
            return $this->fetchUserEventTotalPoints($user, $eventId);
        });

        return response()->json([
            'points' => $pointsWithMilestones,
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

        $eventId = $request->get('event_id');

        $pointsCacheKey = "user_daily_points_{$user->id}_{$date}_{$eventId}";
        Cache::forget($pointsCacheKey);

        $dailyPoints = Cache::remember($pointsCacheKey, now()->addMinutes(15), function () use ($user, $date, $eventId) {
            return $this->fetchUserDailyPoints($user, $date, (int) $eventId, true);
        });

        return response()->json($dailyPoints);
    }

    /**
     * Get next milestone data for the homepage widget.
     */
    public function getNextMilestone(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's preferred event or first participating event
        $eventId = $user->preferred_event_id ?? $user->participations()->first()?->event_id;
        if (! $eventId) {
            return response()->json([
                'next_milestone' => null,
                'previous_milestone' => null,
                'current_distance' => 0,
                'event_name' => null,
            ]);
        }

        $event = Event::find($eventId);
        if (! $event) {
            return response()->json([
                'next_milestone' => null,
                'previous_milestone' => null,
                'current_distance' => 0,
                'event_name' => null,
            ]);
        }

        // Get user's total distance for this event
        $currentDistance = $user->points()
            ->where('event_id', $eventId)
            ->sum('amount');

        // Get next milestone
        $nextMilestone = EventMilestone::where('event_id', $eventId)
            ->where('distance', '>', $currentDistance)
            ->orderBy('distance')
            ->first();

        // Get previous milestone
        $previousMilestone = EventMilestone::where('event_id', $eventId)
            ->where('distance', '<=', $currentDistance)
            ->orderBy('distance', 'desc')
            ->first();

        $nextMilestoneData = null;
        if ($nextMilestone) {
            $nextMilestoneData = [
                'id' => $nextMilestone->id,
                'name' => $nextMilestone->name,
                'distance' => (float) $nextMilestone->distance,
                'description' => $nextMilestone->description,
                'logo_image_url' => $nextMilestone->logo,
                'team_logo_image_url' => $nextMilestone->team_logo,
            ];
        }

        $previousMilestoneData = null;
        if ($previousMilestone) {
            $previousMilestoneData = [
                'id' => $previousMilestone->id,
                'name' => $previousMilestone->name,
                'distance' => (float) $previousMilestone->distance,
                'description' => $previousMilestone->description,
                'logo_image_url' => $previousMilestone->logo,
                'team_logo_image_url' => $previousMilestone->team_logo,
            ];
        }

        return response()->json([
            'next_milestone' => $nextMilestoneData,
            'previous_milestone' => $previousMilestoneData,
            'current_distance' => (float) $currentDistance,
            'event_name' => $event->name,
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

    public function unfollow(Request $request, UndoFollowing $undoFollowing)
    {
        $result = (new UndoFollowing())($request, $request->user());

        // Check if this is an Inertia request
        if ($request->header('X-Inertia')) {
            if (! $result['success']) {
                return redirect()->back()->withErrors(['error' => $result['message']]);
            }

            return redirect()->back()->with('success', $result['message']);
        }

        // Check if this is an AJAX request (non-Inertia)
        if ($request->wantsJson()) {
            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        // Handle regular form submissions with redirects
        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    public function follow_request(Request $request, string $type)
    {
        $result = (new RequestFollow())($request, $request->user(), $type);

        // Check if this is an Inertia request
        if ($request->header('X-Inertia')) {
            if (! $result['success']) {
                return redirect()->back()->withErrors(['error' => $result['message']]);
            }

            return redirect()->back()->with('success', $result['message']);
        }

        // Check if this is an AJAX request (non-Inertia)
        if ($request->wantsJson()) {
            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        }

        // Handle regular form submissions with redirects
        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Set the user's preferred event.
     */
    public function setPreferredEvent(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $user = $request->user();

        // Check if user is participating in the event
        $participation = $user->participations()
            ->where('event_id', $validated['event_id'])
            ->first();

        if (! $participation) {
            return back()->with('error', 'You are not participating in this event.');
        }

        // Update the user's preferred event
        $user->preferred_event_id = $validated['event_id'];
        $user->save();

        return to_route('dashboard')->with('success', 'Preferred event updated successfully.');
    }

    public function getUserEventDetails(Request $request, string $type): JsonResponse
    {
        $user = $request->user();
        $searchTerm = '';

        $teams = Team::where('event_id', $user->preferred_event_id)
            ->where(function ($query) use ($user, $type, $searchTerm) {
                match ($type) {
                    'own' => $query->where('owner_id', $user->id),
                    'other' => $query->where('owner_id', '!=', $user->id),
                    'joined' => $query->whereHas('memberships', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }),
                };

                if ($searchTerm) {
                    $query->where('name', 'ILIKE', "{$searchTerm}%");
                }

                return $query;
            });

        return response()->json([
            'teams' => $teams->get(),
        ]);
    }

    /**
     * Get user's participated events.
     */
    public function getUserParticipatedEvents(Request $request): JsonResponse
    {
        $user = $request->user();

        $participations = $this->userParticipations($user);

        $events = $participations->map(function ($participation) {
            $event = $participation->event;

            return [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'logo_url' => $event->logo_url,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'event_type' => $event->event_type,
                'is_past' => $event->isPastEvent(),
                'is_preferred' => $user->preferred_event_id === $event->id,
                'participation_id' => $participation->id,
            ];
        });

        return response()->json([
            'events' => $events,
        ]);
    }

    public function addManualPoints(Request $request, EventService $eventService): JsonResponse
    {

        $user = $request->user();

        $request->validate([
            'eventId' => [
                'required',
                Rule::exists(Event::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($request, $user) {
                    $userEventParticipation = $user->participations()->where(['event_id' => $request->eventId])->count();

                    if (! $userEventParticipation) {
                        $fail('You are not participating in this event.');

                        return false;
                    }

                    return true;
                },
            ],
        ]);

        $date = Carbon::parse($request->get('date'))->format('Y-m-d');
        $dataSource = DataSource::where('short_name', 'manual')->first();

        foreach ($request->get('points') as $modality => $distance) {
            $eventService->createOrUpdate($user, [
                'date' => $date,
                'distance' => $distance,
                'modality' => $modality,
                'eventId' => $request->eventId,
                'dataSourceId' => $dataSource->id,
            ]);
        }
        $eventService->createOrUpdateUserPoint($user, $request->eventId, $date);

        return response()->json(['message' => 'Points have been added']);

    }
}
