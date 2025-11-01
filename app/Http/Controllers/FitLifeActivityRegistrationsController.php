<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\FitLifeActivityGroup;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

final class FitLifeActivityRegistrationsController extends Controller
{
    public function index_bak(Request $request)
    {
        $user = Auth::user();
        $currentEvent = Event::find($user->preferred_event_id);

        if (! $currentEvent) {
            return redirect()->route('preferred.event')->with('error', 'Please select an event first.');
        }

        // Verify user is participating in this event
        $participation = $user->participations()->where('event_id', $currentEvent->id)->first();
        if (! $participation) {
            return redirect()->route('preferred.event')->with('error', 'You are not participating in this event.');
        }

        $event = $participation->event;

        // Get activities with group information
        $activities = $event->fitActivities()
            ->available()
            ->get()
            ->map(function ($activity) {
                // Find the corresponding group by name
                $group = FitLifeActivityGroup::where('name', $activity->group)->first();

                // Add group logo URL to the activity
                $activity->group_logo_url = $group ? $group->logo_url : null;
                $activity->group_data = $group;

                return $activity;
            });

        dd($activities);

        $activities = $activities->map(function ($activity) use ($user) {
            $activity->description = $this->htmlToPlainText($activity->description);

            $items = $user->questRegistrations()->where('activity_id', $activity->id)->get();

            $registrationCount = $items->count();

            $milestone = $activity->milestones()->select(['id'])->where('total_points', '<=', 1000)->latest('total_points')->first();

            $completedCount = 0;

            if ($registrationCount) {
                foreach ($items as $item) {
                    $hasCount = $item->milestoneStatuses()->where(['milestone_id' => $milestone ? $milestone->id : 0])->count();

                    if ($completedCount) {
                        $completedCount += 1;
                    }
                }

                $activity->is_completed = ($completedCount === $items->count());
            } else {
                $activity->is_completed = false;
            }

            $activity->quest_count = $user->questRegistrations()->where('activity_id', $activity->id)->count();

            return $activity;
        });

        dd($activities);

        $activeTillDate = Carbon::now()->subDays(14)->format('Y-m-d');

        // Get registrations from the last 14 days that are not archived
        $registrations = $user->questRegistrations()
            ->whereHas('activity', function ($query) use ($currentEvent) {
                $query->where('event_id', $currentEvent->id);
            })
            ->where('archived', false)
            ->where('date', '>=', $activeTillDate)
            ->with(['activity'])
            ->orderBy('date')
            ->get();

        dd($registrations);

        return Inertia::render('FitLife/Manage', [
            'registrations' => $registrations,
            'currentEvent' => $currentEvent,
            'activities' => $activities,
        ]);
    }

    /**
     * Display a listing of activity registrations (Manage page)
     */
    public function index(Request $request)
    {
        return Inertia::render('FitLife/Quest/Manage');
    }

    /**
     * Show the form for creating a new activity registration (Schedule page)
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $currentEvent = Event::find($user->preferred_event_id);

        if (! $currentEvent) {
            return redirect()->route('preferred.event')->with('error', 'Please select an event first.');
        }

        // Verify user is participating in this event
        $participation = $user->participations()->where('event_id', $currentEvent->id)->first();
        if (! $participation) {
            return redirect()->route('preferred.event')->with('error', 'You are not participating in this event.');
        }

        // Get available activities
        $activities = $this->getAvailableActivities($currentEvent, $user);
        $datesWithRegistrations = $this->getDatesWithRegistrations($currentEvent, $user);

        return Inertia::render('FitLife/Quest/Schedule', [
            'activities' => $activities,
            'datesWithRegistrations' => $datesWithRegistrations,
            'currentEvent' => $currentEvent,
            'eventStartDate' => $currentEvent->start_date,
            'eventEndDate' => $currentEvent->end_date,
        ]);
    }

    /**
     * Store a newly created activity registration
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $currentEvent = Event::find($user->preferred_event_id);

        if (! $currentEvent) {
            return back()->with('error', 'Please select an event first.');
        }

        // Verify user is participating in this event
        $participation = $user->participations()->where('event_id', $currentEvent->id)->first();
        if (! $participation) {
            return back()->with('error', 'You are not participating in this event.');
        }

        $request->validate([
            'activity_name' => 'required|string',
            'activity_date' => 'required|date',
            'invitees_emails' => 'nullable|string',
        ]);

        try {
            $activityDate = Carbon::parse($request->activity_date);

            // Find the activity using event relationship
            $activity = $currentEvent->fitActivities()
                ->where('name', $request->activity_name)
                ->first();

            if (! $activity) {
                return back()->with('error', "Unable to schedule activity '{$request->activity_name}'. If the problem persists, email us at support@runtheedge.com.");
            }

            // Check if user already has an activity on this date
            $existingRegistration = $user->questRegistrations()
                ->whereHas('activity', function ($query) use ($currentEvent) {
                    $query->where('event_id', $currentEvent->id);
                })
                ->where('date', $activityDate->format('Y-m-d'))
                ->first();

            if ($existingRegistration) {
                return back()->with('error', "You already scheduled activity '{$existingRegistration->activity->name}' on {$activityDate->format('F j, Y')}! Please choose a different date!");
            }

            // Create the registration using activity relationship
            $registration = $activity->registrations()->create([
                'user_id' => $user->id,
                'date' => $activityDate,
                'archived' => false,
            ]);

            // Handle invitations if provided
            if ($request->invitees_emails) {
                $emails = array_map('trim', explode(',', $request->invitees_emails));

                foreach ($emails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $invitee = User::where('email', $email)->first();

                        $activity->invitations()->create([
                            'inviter_id' => $user->id,
                            'invitee_id' => $invitee?->id,
                            'invitee_email' => $email,
                            'secret' => mb_strtoupper(Str::random(32)),
                            'accepted' => false,
                        ]);

                        // TODO: Send email notification
                        // Mail::to($email)->send(new ActivityInvitation(...));
                    }
                }
            }

            return redirect()->route('fit-life-activities.create')
                ->with('success', "Successfully scheduled activity '{$activity->name}'!");

        } catch (Exception $e) {
            Log::error('FitLifeActivityRegistrationsController@store: '.$e->getMessage());

            return back()->with('error', 'Unable to schedule activity. If the problem persists, email us at support@runtheedge.com.');
        }
    }

    /**
     * Show the form for editing the specified activity registration
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        $currentEvent = Event::find($user->preferred_event_id);

        if (! $currentEvent) {
            return redirect()->route('preferred.event')->with('error', 'Please select an event first.');
        }

        $registration = $user->questRegistrations()
            ->with(['activity'])
            ->findOrFail($id);

        $datesWithRegistrations = $this->getDatesWithRegistrations($currentEvent, $user, $id);

        return Inertia::render('FitLife/Quest/Edit', [
            'registration' => $registration,
            'datesWithRegistrations' => $datesWithRegistrations,
            'currentEvent' => $currentEvent,
            'eventStartDate' => $currentEvent->start_date,
            'eventEndDate' => $currentEvent->end_date,
        ]);
    }

    /**
     * Update the specified activity registration
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $currentEvent = Event::find($user->preferred_event_id);

        if (! $currentEvent) {
            return back()->with('error', 'Please select an event first.');
        }

        $registration = $user->questRegistrations()->findOrFail($id);

        $request->validate([
            'activity_date' => 'nullable|date',
            'notes' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:png,gif,jpeg,jpg,webp|max:10240',
        ]);

        try {
            $activityDate = $request->activity_date ? Carbon::parse($request->activity_date) : Carbon::parse($registration->date);

            // Check if another registration exists on the new date
            $otherRegistration = $user->questRegistrations()
                ->whereHas('activity', function ($query) use ($currentEvent) {
                    $query->where('event_id', $currentEvent->id);
                })
                ->where('date', $activityDate->format('Y-m-d'))
                ->where('id', '!=', $id)
                ->first();

            if ($otherRegistration) {
                return back()->with('error', "You already scheduled activity '{$otherRegistration->activity->name}' on {$activityDate->format('F j, Y')}! Please choose a different date!");
            }

            // Update notes
            if ($request->has('notes')) {
                $registration->notes = $request->notes;
            }

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $imageName = uniqid((string) time(), true).'.'.$file->getClientOriginalExtension();

                // Store in public/uploads/quests directory
                $file->move(public_path('uploads/quests'), $imageName);

                // Store only filename in database
                $registration->image = $imageName;
            }

            // Update date
            $registration->date = $activityDate;
            $registration->save();

            return redirect()->route('fit-life-activities.edit', $id)
                ->with('success', "Successfully updated activity '{$registration->activity->name}'!");

        } catch (Exception $e) {
            Log::error('FitLifeActivityRegistrationsController@update: '.$e->getMessage());

            return back()->with('error', 'Unable to edit your scheduled activity. If the problem persists, email us at support@runtheedge.com.');
        }
    }

    /**
     * Archive the specified activity registration
     */
    public function archive(string $id)
    {
        $user = Auth::user();

        try {
            $registration = $user->questRegistrations()->findOrFail($id);

            $registration->archived = true;
            $registration->save();

            return redirect()->route('fit-life-activities.index')
                ->with('success', 'Quest moved to history!');

        } catch (Exception $e) {
            Log::error('FitLifeActivityRegistrationsController@archive: '.$e->getMessage());

            return back()->with('error', 'Unable to archive your activity. If the problem persists, email us at support@runtheedge.com.');
        }
    }

    /**
     * Remove the specified activity registration
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        try {
            $registration = $user->questRegistrations()
                ->with('activity')
                ->findOrFail($id);

            $activityName = $registration->activity->name;

            // Delete related milestone statuses first to avoid foreign key constraint issues
            $registration->milestoneStatuses()->delete();

            // Delete the registration
            $deleted = $registration->delete();

            if (! $deleted) {
                Log::error('FitLifeActivityRegistrationsController@destroy: Failed to delete registration ID: '.$id);

                return back()->with('error', 'Unable to delete your quest. Please try again.');
            }

            return redirect()->back()->with('success', 'Quest deleted successfully!');

        } catch (Exception $e) {
            Log::error('FitLifeActivityRegistrationsController@destroy: '.$e->getMessage());

            return back()->with('error', 'Unable to delete your activity. Please try again. If the problem persists, email us at support@runtheedge.com.');
        }
    }

    /**
     * Display archived activity registrations (History page)
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $currentEvent = Event::find($user->preferred_event_id);

        if (! $currentEvent) {
            return redirect()->route('preferred.event')->with('error', 'Please select an event first.');
        }

        // Verify user is participating in this event
        $participation = $user->participations()->where('event_id', $currentEvent->id)->first();
        if (! $participation) {
            return redirect()->route('preferred.event')->with('error', 'You are not participating in this event.');
        }

        $activeTillDate = Carbon::now()->subDays(14)->format('Y-m-d');

        // Get registrations older than 14 days or archived
        $registrations = $user->questRegistrations()
            ->whereHas('activity', function ($query) use ($currentEvent) {
                $query->where('event_id', $currentEvent->id);
            })
            ->where(function ($query) use ($activeTillDate) {
                $query->where('date', '<', $activeTillDate)
                    ->orWhere('archived', true);
            })
            ->with(['activity'])
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('FitLife/Quest/History', [
            'registrations' => $registrations,
            'currentEvent' => $currentEvent,
        ]);
    }

    public function armoryTrophyCase()
    {
        return Inertia::render('FitLife/Armory/Index');
    }

    public function fitLifeJournal()
    {
        return Inertia::render('FitLife/Journal/Index');
    }

    public function stats()
    {
        return Inertia::render('FitLife/Stats/Index');
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

    /**
     * Get available activities for the current event
     */
    private function getAvailableActivities(Event $event, User $user)
    {
        $activities = DB::select('
            SELECT
                a.id,
                a.name,
                a.data,
                a.description,
                a.group,
                a.total_points,
                (
                    SELECT COUNT(DISTINCT r.id)
                    FROM fit_life_activity_registrations AS r
                    WHERE r.activity_id = a.id
                    AND r.user_id = ?
                ) AS registrations_count
            FROM fit_life_activities AS a
            WHERE a.event_id = ?
            AND (CURRENT_DATE >= a.available_from AND CURRENT_DATE <= a.available_until)
            ORDER BY a.id, a.name ASC
        ', [$user->id, $event->id]);

        // Group activities and add checkmarks
        $activitiesByGroup = [];
        $activityDescriptions = [];

        foreach ($activities as $activity) {
            $activity->data = json_decode($activity->data, true);
            $activity->description = json_decode($activity->description, true);

            if (! isset($activitiesByGroup[$activity->group])) {
                $activitiesByGroup[$activity->group] = [];
            }

            $checkmarks = '';
            if ($activity->registrations_count > 0) {
                if ($activity->registrations_count <= 5) {
                    $checkmarks = ' '.str_repeat('✓', $activity->registrations_count);
                } else {
                    $checkmarks = ' ✓ × '.$activity->registrations_count;
                }
            }

            $activitiesByGroup[$activity->group][] = [
                'label' => $activity->name.$checkmarks,
                'value' => $activity->name,
            ];

            $activityDescriptions[$activity->name] = $activity->description['description'] ?? $activity->description;
        }

        ksort($activitiesByGroup);

        return [
            'byGroup' => $activitiesByGroup,
            'descriptions' => $activityDescriptions,
        ];
    }

    /**
     * Get dates that already have registrations
     */
    private function getDatesWithRegistrations(Event $event, User $user, $excludeRegistrationId = null)
    {
        $query = DB::table('fit_life_activity_registrations')
            ->select('date')
            ->where('user_id', $user->id)
            ->whereIn('activity_id', function ($query) use ($event) {
                $query->select('id')
                    ->from('fit_life_activities')
                    ->where('event_id', $event->id);
            })
            ->where('date', '>=', Carbon::now()->subDays(35))
            ->where('date', '<=', Carbon::now()->addDays(35));

        if ($excludeRegistrationId) {
            $query->where('id', '!=', $excludeRegistrationId);
        }

        return $query->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();
    }
}
