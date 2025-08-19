<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Services\TeamService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class TeamsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $eventId = $user->preferred_event_id;

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        $chutzpahFactorUnit = 1;
        $teamPublicProfile = $team ? $team->public_profile : true;

        $teamMemberSlot = 100;

        if ($team) {
            $teamSettings = json_decode($team->settings, true);

            if (isset($teamSettings['chutzpah_factors'])) {
                $eventSlug = Str::slug($team->event->name);
                $chutzpahFactorUnit = collect($teamSettings['chutzpah_factors'])->filter(function ($value, $key) use ($eventSlug) {
                    return $value[$eventSlug] ?? false;
                })
                    ->map(function ($value, $key) use ($eventSlug) {
                        return $value[$eventSlug];
                    })
                    ->first();

                $teamMemberSlot -= $team->memberships()->count();
            }
        }

        return Inertia::render('teams/index', [
            'team' => $team,
            'chutzpahFactorUnit' => $chutzpahFactorUnit,
            'teamPublicProfile' => $teamPublicProfile,
            'teamMemberSlot' => $teamMemberSlot,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge(['event_id' => $user->preferred_event_id]);

        $team = Team::where(['id' => $request->input('teamId'), 'owner_id' => $user->id, 'event_id' => $request->input('event_id')])->first();

        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id')->where(function ($query) {
                    $query->where('event_type', '!=', 'promotional');
                }),
                function (string $attribute, mixed $value, Closure $fail) use ($request, $user, $team) {

                    if ($team) {
                        return true;
                    }

                    $hasTeam = Team::where(function ($query) use ($user) {
                        return $query->where('owner_id', $user->id)
                            ->orWhereHas('memberships', function ($query) use ($user) {
                                return $query->where('user_id', $user->id);
                            });
                    })->where('event_id', $request->input('event_id'))->count();

                    if ($hasTeam) {
                        $fail('Team has already been created for given event.');

                        return false;
                    }

                    return true;
                },
            ],
            'name' => [
                'required',
                'string',
                Rule::unique(Team::class, 'name')->ignore($team),
            ],
            'public_profile' => 'required|boolean',
        ],
            [
                'event_id.exists' => 'Team can not be added for promotional events.',
                'name.unique' => 'Team name already exists',
            ]);

        $event = Event::find($request->input('event_id'));

        $data = $request->only(['name', 'public_profile', 'event_id']);

        $data['owner_id'] = $user->id;
        $data['settings'] = json_encode(['chutzpah_factors' => [[Str::slug($event->name) => $request->input('chutzpah_factor')]]]);

        if ($team) {
            $team->fill($data)->save();

            return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Team has been updated.']);
        }

        $team = Team::create($data);

        $team->memberships()->create(['user_id' => $user->id, 'event_id' => $team->event_id]);

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Team has been created']);

    }

    public function teamMembers(Request $request): JsonResponse
    {

        $user = $request->user();

        $eventId = $user->preferred_event_id;

        $members = TeamMembership::where(['team_id' => $request->input('teamId'), 'event_id' => $eventId])
            ->limit($request->input('perPage', 5))->paginate()
            ->through(function ($teamMember) use ($eventId) {
                $totalMiles = $teamMember->user->totalPoints()->where('event_id', $eventId)->sum('amount');

                return [
                    'id' => $teamMember->user_id,
                    'name' => $teamMember->user->full_name,
                    'miles' => $totalMiles,
                    'team_id' => $teamMember->team_id,
                    'event_id' => $teamMember->event_id,
                ];
            });

        return response()->json([
            'members' => $members,
        ]);
    }

    public function leaveTeam(Request $request, TeamService $teamService): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'team_id' => [
                'required',
                Rule::exists(Team::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($request, $user) {

                    $team = Team::find($request->input('team_id'));

                    $isTeamMember = $team->memberships()->where('event_id', $request->input('event_id'))->where('user_id', $user->id)->count();

                    if (! $isTeamMember) {
                        $fail('You are not a member of this team.');

                        return false;
                    }

                    return true;
                },
            ],
        ],
            [
                'event_id.exists' => 'Event could not be found for selected team.',
                'team_id.exists' => 'Team could not be found for selected event.',
            ]);

        $team = Team::find($request->input('team_id'));

        $response = $teamService->leaveTeam($team, $user);

        return response()->json(['message' => $response]);
    }
}
