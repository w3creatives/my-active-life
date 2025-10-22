<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\SendTeamInvite;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\TeamMembershipInvite;
use App\Models\TeamMembershipRequest;
use App\Models\TeamPointMonthly;
use App\Models\TeamPointTotal;
use App\Models\User;
use App\Services\TeamService;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
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

        $searchTerm = $request->input('searchUser');

        $members = TeamMembership::where(['team_id' => $request->input('teamId'), 'event_id' => $eventId])
            ->whereHas('user', function ($query) use ($searchTerm) {
                if ($searchTerm) {
                    $query->where('email', 'ILIKE', "%{$searchTerm}%")
                        ->orWhere('first_name', 'ILIKE', "%{$searchTerm}%")
                        ->orWhere('last_name', 'ILIKE', "%{$searchTerm}%")
                        ->orWhere('display_name', 'ILIKE', "%{$searchTerm}%");
                }

                return $query;
            })
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

    public function leaveTeam(Request $request, TeamService $teamService): RedirectResponse
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

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => $response]);

        // return response()->json(['message' => $response]);
        /*$team = Team::find($request->team_id);

        // Remove user from team membership
        $team->memberships()->where(['user_id' => $user->id, 'event_id' => $request->event_id])->delete();

        // If this was the last member and the user was the owner, delete the team
        if ($team->memberships()->count() === 0) {
            if ($team->owner_id === $user->id) {
                $this->deleteTeamForeignData($request->team_id);
                $team->delete();
                return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'You were the last member and team owner. The team has been deleted.']);
            }
        } else {
            // If the leaving user was the owner, transfer ownership to the first remaining member
            if ($team->owner_id === $user->id) {
                $newOwner = $team->memberships()->first();
                if ($newOwner) {
                    $team->fill(['owner_id' => $newOwner->user_id])->save();
                    return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'You have left the team. Team ownership has been transferred to another member.']);
                }
            }
        }

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'You have successfully left the team.']);*/
    }

    public function inviteMembers(Request $request): RedirectResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        // Basic validation first
        $basicValidator = Validator::make($request->all(), [
            'emails.*' => [
                'required',
                'email',
                Rule::exists(User::class, 'email'),
            ],
        ], [
            'emails.*.exists' => 'Ah Shucks! This person (:input) is either not yet registered for this Challenge or this is the wrong email. Check with them and try again!',
        ]);

        if ($basicValidator->fails()) {
            return back()->withErrors($basicValidator)->withInput();
        }

        // Additional business logic validation
        $errors = [];
        foreach ($request->emails as $index => $email) {
            if (empty(trim($email))) {
                continue;
            }

            $member = User::where('email', $email)->first();
            if (! $member) {
                continue;
            }

            $isExistingMember = Team::where(function ($query) use ($member, $eventId) {
                return $query->where('owner_id', $member->id)
                    ->orWhereHas('memberships', function ($query) use ($member, $eventId) {
                        return $query->where('user_id', $member->id)->where('event_id', $eventId);
                    });
            })
                ->where('event_id', $eventId)->where('id', $team->id)->count();

            if ($isExistingMember) {
                $errors["emails.{$index}"] = "Unfortunately, user {$email} already participates in the same team.";

                continue;
            }

            $isExistingMemberInOtherTeam = Team::where(function ($query) use ($member, $eventId) {
                return $query->where('owner_id', $member->id)
                    ->orWhereHas('memberships', function ($query) use ($member, $eventId) {
                        return $query->where('user_id', $member->id)->where('event_id', $eventId);
                    });
            })
                ->where('event_id', $eventId)->where('id', '!=', $team->id)->count();

            if ($isExistingMemberInOtherTeam) {
                $errors["emails.{$index}"] = "Unfortunately, user {$email} already participates in another team.";

                continue;
            }

            $membership = $member->participations()->where(['event_id' => $eventId])->count();
            if (! $membership) {
                $errors["emails.{$index}"] = "Unfortunately, user {$email} is not participating in the event";

                continue;
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $successCount = 0;

        foreach ($request->emails as $email) {
            if (empty(trim($email))) {
                continue;
            }

            $member = User::where('email', $email)->first();

            // Delete any existing invites for this user and team
            $member->invites()->where([
                'status' => 'invite_to_join_issued',
                'team_id' => $team->id,
                'event_id' => $eventId,
            ])->delete();

            // Create new invite
            $invite = $member->invites()->create([
                'status' => 'invite_to_join_issued',
                'team_id' => $team->id,
                'event_id' => $eventId,
            ]);

            // Send email using Laravel's Mail facade
            try {
                Mail::to($member->email)->send(new SendTeamInvite($member, $team, $user));
                $successCount++;
            } catch (Exception $e) {
                // Log the error but continue with other emails
                Log::error('Failed to send team invite email', [
                    'email' => $member->email,
                    'team_id' => $team->id,
                    'error' => $e->getMessage(),
                ]);
                // Still count as success since the invite was created
                $successCount++;
            }
        }

        if ($successCount > 0) {
            $message = $successCount === 1
                ? 'Team membership invitation has been sent to 1 member.'
                : "Team membership invitations have been sent to {$successCount} members.";

            return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => $message]);
        }

        return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'No valid emails provided.']);
    }

    /**
     * View all pending team invites
     */
    public function viewInvites(Request $request): Response
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        $pendingInvites = $team->invites()
            ->with('user:id,first_name,last_name,email,display_name')
            ->where('status', 'invite_to_join_issued')
            ->get()
            ->map(function ($invite) {
                return [
                    'id' => $invite->id,
                    'user_id' => $invite->prospective_member_id,
                    'user_name' => $invite->user->display_name ?? $invite->user->first_name.' '.$invite->user->last_name,
                    'user_email' => $invite->user->email,
                    'status' => $invite->status,
                    'created_at' => $invite->created_at->format('M j, Y g:i A'),
                    'days_ago' => $invite->created_at->diffInDays(now()),
                ];
            });

        return Inertia::render('teams/invites', [
            'team' => $team,
            'pendingInvites' => $pendingInvites,
        ]);
    }

    /**
     * Cancel a specific team invite
     */
    public function cancelInvite(Request $request): RedirectResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $request->validate([
            'invite_id' => 'required|exists:team_membership_invites,id',
        ]);

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        $invite = $team->invites()
            ->where('id', $request->invite_id)
            ->where('status', 'invite_to_join_issued')
            ->first();

        if (! $invite) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Invite not found or already processed.']);
        }

        $invite->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Team invite has been cancelled.']);
    }

    /**
     * Resend a team invite
     */
    public function resendInvite(Request $request): RedirectResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $request->validate([
            'invite_id' => 'required|exists:team_membership_invites,id',
        ]);

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        $invite = $team->invites()
            ->with('user')
            ->where('id', $request->invite_id)
            ->where('status', 'invite_to_join_issued')
            ->first();

        if (! $invite) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Invite not found or already processed.']);
        }

        // Send the email again using Laravel's Mail facade
        try {
            Mail::to($invite->user->email)->send(new SendTeamInvite($invite->user, $team, $user));

            return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Team invite has been resent.']);
        } catch (Exception $e) {
            Log::error('Failed to resend team invite email', [
                'email' => $invite->user->email,
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Failed to resend invite email. Please try again.']);
        }
    }

    /**
     * Bulk cancel expired invites (older than 30 days)
     */
    public function cancelExpiredInvites(Request $request): RedirectResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        $expiredInvites = $team->invites()
            ->where('status', 'invite_to_join_issued')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => "{$expiredInvites} expired invites have been cancelled."]);
    }

    /**
     * Get team invite statistics
     */
    public function inviteStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return response()->json(['error' => 'Team not found.'], 404);
        }

        $stats = [
            'total_invites' => $team->invites()->count(),
            'pending_invites' => $team->invites()->where('status', 'invite_to_join_issued')->count(),
            'accepted_invites' => $team->invites()->where('status', 'accepted')->count(),
            'expired_invites' => $team->invites()
                ->where('status', 'invite_to_join_issued')
                ->where('created_at', '<', now()->subDays(30))
                ->count(),
            'recent_invites' => $team->invites()
                ->where('status', 'invite_to_join_issued')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Accept a team invite via email link
     */
    public function acceptInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'user_id' => 'required|exists:users,id',
            'token' => 'required|string',
        ]);

        $team = Team::findOrFail($request->team_id);
        $user = User::findOrFail($request->user_id);

        // Verify token
        $expectedToken = hash('sha256', $team->id.$user->id.config('app.key'));
        if ($request->token !== $expectedToken) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Invalid or expired invitation link.']);
        }

        // Check if invite exists and is pending
        $invite = $user->invites()
            ->where('team_id', $team->id)
            ->where('status', 'invite_to_join_issued')
            ->first();

        if (! $invite) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Invitation not found or already processed.']);
        }

        // Check if user is already a member
        $isMember = $team->memberships()
            ->where('user_id', $user->id)
            ->where('event_id', $team->event_id)
            ->exists();

        if ($isMember) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'You are already a member of this team.']);
        }

        // Add user to team
        $team->memberships()->create([
            'user_id' => $user->id,
            'event_id' => $team->event_id,
        ]);

        // Update invite status
        $invite->update(['status' => 'accepted']);

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => "Welcome to {$team->name}! You have successfully joined the team."]);
    }

    /**
     * Decline a team invite via email link
     */
    public function declineInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'user_id' => 'required|exists:users,id',
            'token' => 'required|string',
        ]);

        $team = Team::findOrFail($request->team_id);
        $user = User::findOrFail($request->user_id);

        // Verify token
        $expectedToken = hash('sha256', $team->id.$user->id.config('app.key'));
        if ($request->token !== $expectedToken) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Invalid or expired invitation link.']);
        }

        // Check if invite exists and is pending
        $invite = $user->invites()
            ->where('team_id', $team->id)
            ->where('status', 'invite_to_join_issued')
            ->first();

        if (! $invite) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Invitation not found or already processed.']);
        }

        // Update invite status
        $invite->update(['status' => 'declined']);

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => "You have declined the invitation to join {$team->name}."]);
    }

    /**
     * Dissolve a team (delete it completely)
     */
    public function dissolveTeam(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        $user = $request->user();
        $team = $user->teams()->find($request->team_id);

        if (is_null($team)) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        // Check if user is the team owner
        if ($team->owner_id !== $user->id) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Only team owners can dissolve teams.']);
        }

        // Delete team foreign data and the team itself
        $this->deleteTeamForeignData($request->team_id);
        $team->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Team has been dissolved successfully.']);
    }

    /**
     * Transfer team admin role to another member
     */
    public function transferTeamAdminRole(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'member_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();
        $team = $user->teams()->find($request->team_id);

        if (is_null($team)) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        // Check if user is the team owner
        if ($team->owner_id !== $user->id) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Only team owners can transfer admin role.']);
        }

        // Check if the member exists in the team
        $member = $team->memberships()->where(['user_id' => $request->member_id])->first();

        if (is_null($member)) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'User is not a member of this team.']);
        }

        // Transfer admin role
        $team->fill(['owner_id' => $member->user_id])->save();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Team admin role has been transferred successfully.']);
    }

    /**
     * Remove a member from the team
     */
    public function removeMember(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'member_id' => 'required|exists:users,id',
            'event_id' => 'required|exists:events,id',
        ]);

        $user = $request->user();
        $team = $user->teams()->find($request->team_id);

        if (is_null($team)) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        // Check if user is the team owner
        if ($team->owner_id !== $user->id) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Only team owners can remove members.']);
        }

        // Check if trying to remove the team owner
        if ($request->member_id === $user->id) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team owners cannot remove themselves. Use "Leave Team" instead.']);
        }

        // Check if the member exists in the team
        $member = $team->memberships()->where(['user_id' => $request->member_id, 'event_id' => $request->event_id])->first();

        if (is_null($member)) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'User is not a member of this team.']);
        }

        // Remove the member
        $member->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Team member has been removed successfully.']);
    }

    public function findTeamsToJoin(Request $request): JsonResponse
    {
        $user = $request->user();

        $eventId = $user->preferred_event_id;

        $perPage = $request->input('perPage', 5);
        $searchTeam = $request->input('searchTeam');

        $teams = Team::where('event_id', $eventId)
            ->when($searchTeam, function ($query, $searchTeam) {
                return $query->where('name', 'ILIKE', "%{$searchTeam}%");
            })
            ->paginate($perPage)
            ->through(function ($team) use ($eventId, $user) {

                $totalMiles = $team->totalPoints()->where('event_id', $eventId)->sum('amount');
                $totalMembers = $team->memberships()->where('event_id', $eventId)->count();

                $membershipStatus = 'Request Join';
                $hoverStatusText = 'Request Join';

                if ($team->requests()->where(['prospective_member_id' => $user->id, 'event_id' => $eventId])->count()) {
                    $membershipStatus = 'Requested Join';
                    $hoverStatusText = 'Cancel Requested';
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'mileage' => round((float) $totalMiles, 1),
                    'members' => $totalMembers,
                    'public_profile' => $team->public_profile,
                    'membership' => [
                        'status' => str_replace([' '], [''], $membershipStatus),
                        'text' => $membershipStatus,
                        'hover_status' => str_replace([' '], [''], $hoverStatusText),
                        'hover_text' => $hoverStatusText,
                    ],
                ];
            });

        return response()->json([
            'teams' => $teams,
        ]);
    }

    /**
     * Handle team join request from a user
     */
    public function teamJoinRequest(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class, 'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();

        $team = Team::where(['id' => $request->team_id, 'event_id' => $request->event_id])->first();

        if (is_null($team)) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'Team does not belong to given event ID']);
        }

        // Check if user is already a member of this team
        $hasTeamMember = $team->memberships()->where(['user_id' => $user->id, 'event_id' => $request->event_id])->count();

        if ($hasTeamMember) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'You are already a member of this team']);
        }

        // Check if user is already a member of another team for this event
        $hasOtherTeamMembership = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $request->event_id)->where('id', '!=', $request->team_id)->exists();

        if ($hasOtherTeamMembership) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'You are already a member of another team for this event']);
        }

        // Check if user already has an invite to this team
        $hasInvite = $user->invites()->where(['team_id' => $request->team_id, 'event_id' => $request->event_id])->count();

        if ($hasInvite) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'You already have an invite to join this team']);
        }

        // Check if user already has a pending request to join this team
        $hasRequest = $user->requests()->where(['team_id' => $request->team_id, 'event_id' => $request->event_id])->count();

        if ($hasRequest) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'Your request to join this team already exists']);
        }

        // Check if user already has ANY pending join request for this event
        $hasAnyPendingRequest = $user->requests()
            ->where('event_id', $request->event_id)
            ->where('status', 'request_to_join_issued')
            ->exists();

        if ($hasAnyPendingRequest) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'You already have a pending join request. Please wait for it to be processed or cancel it before sending another.']);
        }

        // Always create a join request (admin approval required for all teams)
        $user->requests()->create(['team_id' => $request->team_id, 'event_id' => $request->event_id, 'status' => 'request_to_join_issued']);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => 'Your request to join the team has been sent and is awaiting approval']);
    }

    /**
     * Get user's team invitations
     */
    public function getUserTeamInvitations(Request $request): JsonResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        // Get user's pending invitations
        $invitations = $user->invites()
            ->with(['team:id,name,event_id'])
            ->where('event_id', $eventId)
            ->where('status', 'invite_to_join_issued')
            ->get()
            ->map(function ($invite) {
                return [
                    'id' => $invite->id,
                    'team_id' => $invite->team_id,
                    'team_name' => $invite->team->name,
                    'event_id' => $invite->event_id,
                    'created_at' => $invite->created_at->format('M j, Y g:i A'),
                    'days_ago' => $invite->created_at->diffInDays(now()),
                    'status' => $invite->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $invitations,
        ]);
    }

    /**
     * Accept a team invitation
     */
    public function acceptTeamInvitation(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class, 'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();

        // Check if user is already in a team
        $hasTeam = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $request->event_id)->exists();

        if ($hasTeam) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'You are already a member of a team. Please leave your current team first.']);
        }

        $invitation = $user->invites()
            ->where(['team_id' => $request->team_id, 'event_id' => $request->event_id])
            ->where('status', 'invite_to_join_issued')
            ->first();

        if (is_null($invitation)) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'Team invitation not found or already processed.']);
        }

        $team = $invitation->team;

        if (is_null($team)) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        // Add user to team
        $team->memberships()->create([
            'user_id' => $user->id,
            'event_id' => $request->event_id,
        ]);

        // Delete the invitation
        $invitation->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => "Welcome to {$team->name}! You have successfully joined the team."]);
    }

    /**
     * Decline a team invitation
     */
    public function declineTeamInvitation(Request $request): RedirectResponse
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class, 'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();

        $invitation = $user->invites()
            ->where(['team_id' => $request->team_id, 'event_id' => $request->event_id])
            ->where('status', 'invite_to_join_issued')
            ->first();

        if (is_null($invitation)) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => 'Team invitation not found or already processed.']);
        }

        // Delete the invitation
        $invitation->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Invitation declined successfully.']);
    }

    /**
     * Get user's outgoing join requests (teams the user has requested to join)
     */
    public function getUserJoinRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $joinRequests = $user->requests()
            ->with(['team' => function ($query) {
                return $query->select(['id', 'name', 'event_id', 'public_profile']);
            }])
            ->where('event_id', $eventId)
            ->where('status', 'request_to_join_issued')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'team_id' => $request->team_id,
                    'team_name' => $request->team->name,
                    'status' => $request->status,
                    'created_at' => $request->created_at->format('M j, Y g:i A'),
                    'days_ago' => $request->created_at->diffInDays(now()),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $joinRequests,
        ]);
    }

    /**
     * Cancel user's outgoing join request
     */
    public function cancelUserJoinRequest(Request $request): RedirectResponse
    {
        $request->validate([
            'request_id' => 'required|exists:team_membership_requests,id',
        ]);

        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $joinRequest = $user->requests()
            ->where('id', $request->request_id)
            ->where('event_id', $eventId)
            ->where('status', 'request_to_join_issued')
            ->first();

        if (! $joinRequest) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Request not found or already processed.']);
        }

        $joinRequest->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Join request has been cancelled.']);
    }

    /**
     * Get team membership requests (users requesting to join the team)
     */
    public function membershipRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return response()->json(['error' => 'Team not found.'], 404);
        }

        $memberRequests = $team->requests()
            ->with(['user' => function ($query) {
                return $query->select(['id', 'first_name', 'last_name', 'display_name', 'email']);
            }])
            ->where('event_id', $eventId)
            ->where('status', 'request_to_join_issued')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user_id' => $request->prospective_member_id,
                    'user_name' => $request->user->display_name ?? $request->user->first_name.' '.$request->user->last_name,
                    'user_email' => $request->user->email,
                    'status' => $request->status,
                    'created_at' => $request->created_at->format('M j, Y g:i A'),
                    'days_ago' => $request->created_at->diffInDays(now()),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $memberRequests,
        ]);
    }

    /**
     * Accept or decline team membership request
     */
    public function handleMembershipRequest(Request $request): RedirectResponse
    {
        $request->validate([
            'request_id' => 'required|exists:team_membership_requests,id',
            'action' => 'required|in:accept,decline',
        ]);

        $user = $request->user();
        $eventId = $user->preferred_event_id;

        $team = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
        })->where('event_id', $eventId)->first();

        if (! $team) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Team not found.']);
        }

        $membershipRequest = $team->requests()
            ->where('id', $request->request_id)
            ->where('status', 'request_to_join_issued')
            ->first();

        if (! $membershipRequest) {
            return redirect()->route('teams')->with('alert', ['type' => 'error', 'message' => 'Request not found or already processed.']);
        }

        if ($request->action === 'accept') {
            // Add user to team
            $team->memberships()->create([
                'user_id' => $membershipRequest->prospective_member_id,
                'event_id' => $eventId,
            ]);

            // Delete the request
            $membershipRequest->delete();

            return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Member request has been accepted.']);
        }

        // Decline the request
        $membershipRequest->delete();

        return redirect()->route('teams')->with('alert', ['type' => 'success', 'message' => 'Member request has been declined.']);
    }

    /**
     * Delete team foreign data (helper method)
     */
    private function deleteTeamForeignData($teamId): void
    {
        $tables = DB::select("select table_name from information_schema.columns where column_name = 'team_id'");

        foreach ($tables as $table) {
            DB::table($table->table_name)->where('team_id', $teamId)->delete();
        }
        /**
        // Delete team memberships
        TeamMembership::where('team_id', $teamId)->delete();

        // Delete team invites
        TeamMembershipInvite::where('team_id', $teamId)->delete();

        // Delete team requests
        TeamMembershipRequest::where('team_id', $teamId)->delete();

        // Delete team follows
        \App\Models\TeamFollow::where('team_id', $teamId)->delete();

        // Delete team follow requests
        \App\Models\TeamFollowRequest::where('team_id', $teamId)->delete();

        // Delete team points
        TeamPointTotal::where('team_id', $teamId)->delete();
        TeamPointMonthly::where('team_id', $teamId)->delete();
        */
    }
}
