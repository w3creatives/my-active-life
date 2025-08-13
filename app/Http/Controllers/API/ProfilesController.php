<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Models\DataSource;
use App\Models\DataSourceProfile;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamFollowRequest;
use App\Models\User;
use App\Services\EventService;
use App\Services\TeamService;
use App\Services\UserService;
use App\Services\DeviceService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class ProfilesController extends BaseController
{
    public function __construct(
        private TeamService $teamService
    ) {}

    public function show(Request $request, UserService $userService): JsonResponse
    {
        $user = $request->user();

        $basicProfile = $userService->basic($request);
        unset($basicProfile['display_name']);

        // Add additional fields similar to login endpoint
        $preferredTeam = Team::where(function ($query) use ($user) {
            return $query->where('owner_id', $user->id)->where('event_id', $user->preferred_event_id)
                ->orWhereHas('memberships', function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where('event_id', $user->preferred_event_id);
                });
        })->first();

        $basicProfile['id'] = $user->id;
        $basicProfile['name'] = $user->display_name;
        $basicProfile['has_team'] = (bool) $preferredTeam;
        $basicProfile['preferred_team_id'] = $preferredTeam ? $preferredTeam->id : null;
        $basicProfile['preferred_team'] = $preferredTeam;

        $basicProfile['timezones'] = $userService->timezones();

        return $this->sendResponse($basicProfile, 'Response');
    }

    public function all(Request $request, UserService $userService)
    {
        $profile = $userService->profile($request);
        $profile['timezones'] = $userService->timezones();

        return $this->sendResponse($profile, 'Response');
    }

    public function store(Request $request): JsonResponse
    {

        $user = $request->user();

        $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique((new User)->getTable())->ignore($user->id),
            ],
            'preferred_event_id' => 'required|numeric',
            'first_name' => 'required',
            'last_name' => 'required',
            'display_name' => 'required',
            'time_zone' => 'required',
            'gender' => 'required',
            'shirt_size' => 'required',
            'settings' => 'required|json',
        ]);

        $user->fill($request->only([
            'email',
            'first_name',
            'last_name',
            'display_name',
            'birthday',
            'bio',
            'time_zone',
            'street_address1',
            'street_address2',
            'city',
            'state',
            'country',
            'zipcode',
            'gender',
            'settings',
            'shirt_size',
            'preferred_event_id',
        ]))->save();

        return $this->sendResponse([], 'Profile Updated');
    }

    public function sourceProfile(Request $request): JsonResponse
    {

        $user = $request->user();

        $pageNum = $request->page ?? 1;

        $cacheName = "source_profile_{$user->id}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);
            //  return $this->sendResponse($item, 'Response');
        }

        $profiles = DataSource::select(['id', 'name', 'short_name', 'description', 'resynchronizable', 'profile'])
            ->with(['sourceProfile' => function ($query) use ($user) {
                return $query->where('user_id', $user->id)->selectRaw('access_token, refresh_token, token_expires_at, data_source_id, updated_at last_updated');
            }])
            ->get()
            ->map(function ($item) use ($user) {

                $authUrl = DataSource::authUrls($item->short_name);
                if ($authUrl) {
                    $item->oauth_url = $authUrl.'?'.http_build_query(['uid' => $user->id]);
                } else {
                    $item->oauth_url = null;
                }

                return $item;
            });
        Cache::put($cacheName, $profiles, now()->addHours(2));

        return $this->sendResponse($profiles, '');
    }

    public function create(Request $request): JsonResponse
    {

        $user = $request->user();

        $request->validate(
            [
                'data_source_id' => [
                    'required',
                    // Rule::unique((new DataSourceProfile)->getTable(),'data_source_id')->ignore($user->id, 'user_id'),
                    Rule::unique((new DataSourceProfile)->getTable(), 'data_source_id')
                        ->using(function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        }),
                ],
                'access_token' => 'required',
                'refresh_token' => '', // required remove to save gramin record. Gramin does not have this value
                'token_expires_at' => 'date', // required remove to save gramin record. Gramin does not have this value
                'access_token_secret' => 'required',
            ],
            [
                'data_source_id.unique' => 'Source already exists',
            ]
        );

        $sourceProfileData = $request->only(['data_source_id', 'access_token', 'refresh_token', 'token_expires_at', 'access_token_secret']);
        $sourceProfileData['sync_mode'] = 'mobile';
        $profile = $user->profiles()->create($sourceProfileData);
        $user->logSourceConnected(['data_source_id' => $profile->data_source_id, 'action_source' => 'mobile']);

        return $this->sendResponse([], 'Data source saved');
    }

    public function destroy(Request $request, EventService $eventService, DeviceService $deviceService): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'data_source_id' => [
                'required',
            ],
            'synced_mile_action' => 'required|in:preserve,delete',
        ]);

        $profile = $user->profiles()->where('data_source_id', $request->data_source_id)->first();

        if (! $profile) {
            return $this->sendError('Data source is not connected', ['error' => 'Data source is not connected']);
        }

        $hasRevoked = $deviceService->revoke($profile);

        if($hasRevoked == false)
        {
            $user->logSourceDisconnected(['data_source_id' => $profile->data_source_id, 'action_source' => 'mobile','action' => 'REVOKE_FAILED']);
            return $this->sendError('Data source could not be disconnected, please try again', ['error' => 'Data source could not be disconnected, please try again']);
        }

        if ($request->synced_mile_action === 'delete') {
            $eventService->deleteSourceSyncedMile($user, $profile->data_source_id);
        }

        $user->logSourceDisconnected(['data_source_id' => $profile->data_source_id, 'action_source' => 'mobile']);

        $profile->delete();

        return $this->sendResponse([], 'Data source deleted');
    }

    public function updateNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|in:bibs,follow_requests,team_bibs,team_follow_requests,team_updates',
            'notification_enabled' => 'required|boolean',
        ]);

        $settings = json_decode($user->settings, true);

        $isEnabled = (bool) $request->notification_enabled;

        $deniedNotifications = $settings['denied_notifications'];

        // if(in_array($request->name, $deniedNotifications)){

        if (! $isEnabled) {
            $deniedNotifications[] = $request->name;
        } elseif (in_array($request->name, $deniedNotifications)) {

            $deniedNotifications = array_flip($deniedNotifications);
            unset($deniedNotifications[$request->name]);
            $deniedNotifications = array_flip($deniedNotifications);
        }

        // }

        $settings['denied_notifications'] = array_values($deniedNotifications);

        $user->fill(['settings' => json_encode($settings)])->save();

        return $this->sendResponse([], 'Notification updated');
    }

    public function updateManualEntry(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'manual_entry' => 'required|boolean',
        ]);

        $settings = json_decode($user->settings, true);

        $settings['manual_entry_populates_all_events'] = (bool) $request->manual_entry;

        $user->fill(['settings' => json_encode($settings)])->save();

        return $this->sendResponse([], 'Settings updated');
    }

    public function updateTrackerAt(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'attitude' => 'required|in:default,yoda,tough_love,positive,cheerleader,scifi,historian,superhero',
        ]);

        $settings = json_decode($user->settings, true);

        $settings['attitude'] = $request->attitude;

        $user->fill(['settings' => json_encode($settings)])->save();

        return $this->sendResponse([], sprintf("Successfully updated your Tracker's attitude to %s.", ucwords(str_replace('_', ' ', $request->attitude))));
    }

    public function rtyMileageGoal(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'mileage_goal' => 'required',
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
        ]);

        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        $settings = json_decode($user->settings, true);

        $rtyGoals = (isset($settings['rty_goals'])) ? $settings['rty_goals'] : [];

        $eventSlug = Str::slug($event->name);

        $mileageGoal = $request->mileage_goal;

        $hasRtyGoal = collect($rtyGoals)->where($eventSlug, '!=', null)->count();

        if ($hasRtyGoal) {
            $rtyGoals = collect($rtyGoals)->map(function ($goal) use ($eventSlug, $mileageGoal) {
                if (in_array($eventSlug, array_keys($goal))) {
                    $goal[$eventSlug] = $mileageGoal;
                }

                return $goal;
            })->toArray();
        } else {
            $rtyGoals[] = [$eventSlug => $mileageGoal];
        }

        $settings['rty_goals'] = $rtyGoals;

        $user->fill(['settings' => json_encode($settings)])->save();

        return $this->sendResponse([], sprintf('Successfully updated your goal for %s to be %s.', $event->name, $mileageGoal));
    }

    public function updateEventModality(Request $request): JsonResponse
    {

        $request->validate([
            'name' => 'required|in:daily_steps,run,walk,swim,bike,other',
            'notification_enabled' => 'required|boolean',
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
        ]);

        $user = $request->user();

        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $settings = json_decode($participation->settings, true);

        $modalityName = $request->name;

        $notificationEnabled = $request->notification_enabled;

        $modalityOverrides = isset($settings['modality_overrides']) ? $settings['modality_overrides'] : [];

        $collection = collect($modalityOverrides);

        $modalityOverrides = $collection->merge($modalityName);

        $modalityOverrides = $modalityOverrides->filter(function ($item) use ($modalityName, $notificationEnabled) {
            if (! $notificationEnabled) {
                return $modalityName !== $item;
            }

            return true;
        })->unique()->values()->toArray();

        $settings['modality_overrides'] = $modalityOverrides;

        $participation->fill(['settings' => json_encode($settings)])->save();

        return $this->sendResponse([], 'Notification updated');
    }

    public function findSetting(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'setting' => 'required|in:notification,manual_entry,attitude,rty_mileage_goal,modalities',
            'event_id' => [
                Rule::requiredIf(in_array($request->setting, ['rty_mileage_goal', 'modalities'])),
            ],
        ]);
        $cacheName = "user_setting_{$user->id}_{$request->setting}_{$request->event_id}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);
            //  return $this->sendResponse($item, 'Response');
        }
        $settings = json_decode($user->settings, true);

        $data = [];

        switch ($request->setting) {
            case 'notification':
                $notifications = ['bibs', 'follow_requests', 'team_bibs', 'team_follow_requests', 'team_updates'];

                $data['notifications'] = collect($notifications)->map(function ($item) use ($settings) {
                    return [
                        'name' => $item,
                        'notification_enabled' => ! in_array($item, $settings['denied_notifications']),
                    ];
                });
                break;
            case 'manual_entry':
                $data['manual_entry'] = $settings['manual_entry_populates_all_events'];
                break;

            case 'attitude':
                $data['attitude'] = (isset($settings['attitude'])) ? $settings['attitude'] : 'default';
                $data['all_attitudes'] = [
                    'default' => 'Relaxed',
                    'yoda' => 'Yoda',
                    'tough_love' => 'Tough Love',
                    'positive' => 'Positive',
                    'cheerleader' => 'Cheerleader',
                    'scifi' => 'Sci-Fi',
                    'historian' => 'Historian',
                    'superhero' => 'Super Hero',
                ];
                break;
            case 'rty_mileage_goal':
                $participation = $user->participations()->where('event_id', $request->event_id)->first();

                if (is_null($participation)) {
                    return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
                }

                $event = $participation->event;

                $eventSlug = Str::slug($event->name);

                $rtyGoals = (isset($settings['rty_goals'])) ? $settings['rty_goals'] : [];

                $rtyGoal = collect($rtyGoals)->filter(function ($goal) use ($eventSlug) {
                    return in_array($eventSlug, array_keys($goal));
                })
                    ->pluck($eventSlug)->first();

                $distance = $rtyGoal;

                if (! $rtyGoal) {
                    $distance = $event->total_points;
                }

                // $distance = (float)$distance;

                // $totalDays = Carbon::parse($participation->subscription_end_date)->diffInDays(Carbon::parse($event->start_date)->subDay(0));
                $period = CarbonPeriod::create($event->start_date, $participation->subscription_end_date);
                $totalDays = $period->count();

                $userPoint = $user->points()->selectRaw('SUM(amount) AS total_mile')->where('event_id', $event->id)->where('date', '>=', Carbon::parse($event->start_date)->format('Y-m-d'))->where('date', '<=', Carbon::now()->format('Y-m-d'))->first();

                $userTotalPointDays = 0; // $user->points()->where('event_id',$event->id)->where('date','>=',Carbon::parse($event->start_date)->format('Y-m-d'))->where('date','<=', Carbon::now()->format('Y-m-d'))->groupBy('date')->count();

                $userTotalPoints = (float)$userPoint->total_mile;

                // $totalDayTillToday = Carbon::now()->diffInDays(Carbon::parse($event->start_date));
                $period = CarbonPeriod::create($event->start_date, Carbon::now()->toDateString());
                $totalDayTillToday = $period->count();

                $totalRemainingDays = (float)($totalDays - $totalDayTillToday);

                if (! $totalDayTillToday) {
                    $totalDayTillToday = 1;
                }

                if (! $totalRemainingDays) {
                    $totalRemainingDays = 1;
                }

                // $perDayAvgMile = (float)($userTotalPoints / ($totalDayTillToday));
                // $perDayAvgMileRequired = (float)(($distance - $userTotalPoints) / ($totalRemainingDays));
                $perDayAvgMile = number_format($userTotalPoints/($totalDayTillToday), 2, '.', '');
                $perDayAvgMileRequired = number_format(($distance - $userTotalPoints)/($totalRemainingDays), 2, '.', '');

                $completedPercentage = ($userTotalPoints * 100) / $distance;

                $subEndDate = Carbon::parse($participation->subscription_end_date)->format('F dS, Y');

                $estimatedCompletionDate = $participation->subscription_end_date;

                /**
                $message = '';
                if ($perDayAvgMileRequired > $perDayAvgMile) {
                $message .= "You are behind the mileage you need to reach your goal. No neeed to panic, though. Get out and giddy up!\nSo far, you averaged {$perDayAvgMile} miles per day. Moving forward, {$perDayAvgMileRequired} miles per day would ensure you reach your goal in time. If that sounds like too much, please adjust your goal in Account Settings.";
                } else {
                // Calculate estimated completion date based on current pace
                $remainingMiles = $distance - $userTotalPoints;
                $daysToComplete = ceil($remainingMiles / $perDayAvgMile);
                $estimatedCompletionDate = Carbon::now()->addDays($daysToComplete)->format('F dS, Y');

                $message .= "Isn't this amazing!? Consistency and proper pacing led you to this point. Be proud and rejoice on {$estimatedCompletionDate}!\nSo far, you averaged {$perDayAvgMile} miles per day. Moving forward, {$perDayAvgMileRequired} miles per day would ensure you reach your goal in time. Great job! You are overachieving!\nIf you were to decrease your average daily mileage from {$perDayAvgMile} miles per day to {$perDayAvgMileRequired} miles per day, you would still reach your goal on {$subEndDate}.";
                }
                 */
                $attitude = (isset($settings['attitude']))?$settings['attitude']:'default';
                $userTotalPoints = (float) number_format($userTotalPoints, 2, '.', '');

                $on_target_mileage = ($totalDayTillToday + 1) * $perDayAvgMile;

                if( $on_target_mileage > 0 ) {
                    $on_target_percentage = (float) number_format( (($userTotalPoints / $on_target_mileage) * 100), 2, '.', '');

                    if( $userTotalPoints >= $on_target_mileage ) {
                        $goalMessage['message'] = "You did it! You crushed the {$on_target_mileage} miles goal! Yay!";
                        $goalMessage['indicator'] = '';
                    } else {
                        $goalMessage = $this->getGoalMessage($attitude, $on_target_percentage, $estimatedCompletionDate);
                    }
                } else {
                    $goalMessage['message'] = "";
                    $goalMessage['indicator'] = "";
                }

                $data = [
                    'rty_mileage_goal' => $rtyGoal,
                    'mileage_per_day' => $perDayAvgMile,
                    'mileage_required_per_day' => $perDayAvgMileRequired,
                    'total_miles' => $distance,
                    'completed_miles' => $userTotalPoints,
                    'completed_percentage' => number_format($completedPercentage,2,'.',''),
                    'completion_date' => $participation->subscription_end_date,
                    'estimated_completion_date' => $estimatedCompletionDate,
                    'goal_message' => !empty($goalMessage['message']) ? $goalMessage['message'] : '',
                    'goal_indicator' => !empty($goalMessage['indicator']) ? $goalMessage['indicator'] : '',
                    'extra' => compact('totalRemainingDays','userTotalPoints','totalDayTillToday','totalDays','userTotalPointDays','event','participation'),
                    //'completion_date_formatted' => Carbon::parse($participation->subscription_end_date)->format('M Do Y'),
                ];
                break;
            case 'modalities':
                $participation = $user->participations()->where('event_id', $request->event_id)->first();

                if (is_null($participation)) {
                    return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
                }

                $settings = json_decode($participation->settings, true);

                $modalityOverrides = isset($settings['modality_overrides']) ? $settings['modality_overrides'] : [];

                $modalities = ['daily_steps', 'run', 'walk', 'swim', 'bike', 'other'];

                $data['modalities'] = collect($modalities)->map(function ($item) use ($modalityOverrides) {
                    return [
                        'name' => $item,
                        'notification_enabled' => in_array($item, $modalityOverrides),
                    ];
                });

                break;
        }

        Cache::put($cacheName, $data, now()->addHours(2));

        return $this->sendResponse($data, 'User Settings');
    }

    public function getGoalMessage($attitude, $on_target_percentage, $expected_finish_date)
    {
        $goalMessage = [];

        if( $on_target_percentage < 80 ) {
            $goalMessage['indicator'] = 'behind';
            switch ($attitude) {
                case 'yoda':
                    $goalMessage['message'] = 'No! Try not! Do or do not. There is no try. Get more miles, you must! Or change your goal, you can.';
                    break;

                case 'positive';
                    $goalMessage['message'] = 'You can do this! To meet your current goal you just need to get rolling! Of course, you can also change your goal in Account Settings.';
                    break;

                case 'tough_love';
                    $goalMessage['message'] = "Stop reading this and go get some miles! You need to get your BUTT IN GEAR to get back on pace! You've got this! I'll make sure of it!";
                    break;

                case 'cheerleader';
                    $goalMessage['message'] = "No frowns allowed, only cheers here! Let’s turn up the energy and make it a great year!";
                    break;

                case "scifi";
                    $goalMessage['message'] = "Your current trajectory is off-course. Redirect your energy to the hyperlanes, and you'll reach light speed in no time!";
                    break;

                case "historian";
                    $goalMessage['message'] = "Remember, Rome wasn't built in a day, and your goal isn't missed in one. Regroup and march on!";
                    break;

                case "superhero";
                    $goalMessage['message'] = "This is no time for a cliffhanger! Summon your superpowers and let's get you back in the hero game. The city believes in you!";
                    break;

                default:
                    $goalMessage['message'] = "Ruh-roh! You are behind the mileage you need to reach your goal. No neeed to panic, though. Get out and giddy up!";
                    break;
            }
        }

        if( $on_target_percentage >= 80 && $on_target_percentage < 100 ) {
            $goalMessage['indicator'] = 'nearly there';
            switch ($attitude) {
                case 'yoda':
                    $goalMessage['message'] = "So close, you are, with just a few more miles each day, reach your goal, you will. Hmmmmmm.";
                    break;

                case 'positive';
                    $goalMessage['message'] = "We believe in you! Every day is a new day. Start fresh and set your sights high to get back on track! You're so close!";
                    break;

                case 'tough_love';
                    $goalMessage['message'] = "Hmm really? Is this all you got? Get your butt moving! This is embarrassing.";
                    break;

                case 'cheerleader';
                    $goalMessage['message'] = "Hop, skip, and a jump to get back on pace! You’ve gotta keep going with much more haste!";
                    break;

                case "scifi";
                    $goalMessage['message'] = "Engage hyperdrive! A small boost in your thrusters will realign you with your starbound trajectory.";
                    break;

                case "historian";
                    $goalMessage['message'] = "History is filled with near-misses that became great triumphs. Yours is within reach—forge ahead!";
                    break;

                case "superhero";
                    $goalMessage['message'] = "Every hero faces trials. Yours is just a sprint away. Power up, hero—it's time for an epic comeback!";
                    break;

                default:
                    $goalMessage['message'] = "Good start there, friend. If you up the ante just a tad, you will find your way to the finish line easy-peasy.";
                    break;
            }
        }

        if( $on_target_percentage >= 100 && $on_target_percentage < 120 ) {
            $goalMessage['indicator'] = 'on target or overachieving';
            switch ($attitude) {
                case 'yoda':
                    $goalMessage['message'] = "I sense that, focused on your goal, you are. An overachiever, you may even be. The way, this is. On {$expected_finish_date}, celebrate, you will!";
                    break;

                case 'positive';
                    $goalMessage['message'] = "WHOA! You're crushing this challenge! Keep it up, you're on pace to finish on {$expected_finish_date} and not a day later!";
                    break;

                case 'tough_love';
                    $goalMessage['message'] = "So you think you're hot stuff, don't you? So what if you're expected to finish on {$expected_finish_date}, you could finish faster if you actually tried.";
                    break;

                case 'cheerleader';
                    $goalMessage['message'] = "You're doing GREAT! Keep up this pep! You'll be crossing that finish line with a flair in your step! {$expected_finish_date}.";
                    break;

                case "scifi";
                    $goalMessage['message'] = "Steady as a spaceship on its mission, you are in perfect alignment with the universal finish date of {$expected_finish_date}.";
                    break;

                case "historian";
                    $goalMessage['message'] = "Like the great explorers of old, you are charting a steady course to your own discovery—finishing on {$expected_finish_date}.";
                    break;

                case "superhero";
                    $goalMessage['message'] = "Steadfast as a hero's resolve, you're flying straight and true. Maintain this heroic pace for a victorious celebration on {$expected_finish_date}.";
                    break;

                default:
                    $goalMessage['message'] = "Isn't this amazing!? Consistency and proper pacing led you to this point. Be proud and rejoice on {$expected_finish_date}!";
                    break;
            }
        }

        if( $on_target_percentage >= 100 && $on_target_percentage >= 120 ) {
            $goalMessage['indicator'] = 'ahead';
            switch ($attitude) {
                case 'yoda':
                    $goalMessage['message'] = "Powerful you have become but future is always changing. Finish you will on approximately {$expected_finish_date}.";
                    break;

                case 'positive';
                    $goalMessage['message'] = "Wow! You are amazing and well ahead of your goal! You are on target to finish approximately on {$expected_finish_date}.";
                    break;

                case 'tough_love';
                    $goalMessage['message'] = "Don't get cocky just because you are ahead of pace. You still have a lot to prove so go get more miles!";
                    break;

                case 'cheerleader';
                    $goalMessage['message'] = "Pom-poms out, you're leading the pack! Keep that sparkle and stay on track! {$expected_finish_date}!";
                    break;

                case "scifi";
                    $goalMessage['message'] = "Warp speed ahead! You're racing through the galaxy and on track to orbit the finish star on {$expected_finish_date}.";
                    break;

                case "historian";
                    $goalMessage['message'] = "Bravo! You're setting a pace worthy of the history books, racing towards a triumphant finish on {$expected_finish_date}.";
                    break;

                case "superhero";
                    $goalMessage['message'] = "Incredible! With great power comes great progress! You're soaring above the skyline, on course to save the day on {$expected_finish_date}.";
                    break;

                default:
                    $goalMessage['message'] = "Nicely done! You are ahead! Even more ice-cream for you! You are predicted to finish approximately on {$expected_finish_date}.";
                    break;
            }
        }

        return $goalMessage;
    }

    public function eventParticipants(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $request->page ?? 1;
        $cacheName = "user_event_participant_{$user->id}_{$page}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);
            // return $this->sendResponse($item, 'Response');
        }

        $participations = $user->participations()
            ->where('subscription_end_date', '>=', Carbon::now()->format('Y-m-d'))
            ->with('event')
            ->whereHas('event', function($query) {
                $query->where('mobile_event', true);
            })
            ->simplePaginate(100)
            ->through(function ($participation) use ($user) {

                $event = $participation->event;

                $event['supported_modalities'] = $this->decodeModalities($event['supported_modalities']);

                $membership = $user->memberships()->where(['event_id' => $event->id])->first();

                $team = $membership ? $membership->team : null;

                if ($team) {
                    $team->is_team_owner = $team->owner_id === $user->id;
                }

                $event->has_team = ! is_null($membership);
                $event->preferred_team_id = $membership ? $team->id : null;
                $event->preferred_team = $membership ? $team : null;
                $event->is_expired = ! Carbon::parse($event->end_date)->gt(Carbon::now());

                $participation->event = $event;

                return $participation;
            });
        Cache::put($cacheName, $participations, now()->addHours(2));

        return $this->sendResponse($participations, 'Response');
    }

    public function updateEventParticipantPrivacy(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
            'public_profile' => 'required|boolean',
        ]);

        $user = $request->user();

        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        $participation->fill(['public_profile' => (bool) $request->public_profile])->save();

        return $this->sendResponse([], 'Privacy updated');
    }

    public function requestTeamFollow(Request $request, $type = null): JsonResponse
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
            return $this->sendError('Team not found', ['error' => 'Team not found']);
        }

        $followRequest = $team->followerRequests()->where(['prospective_follower_id' => $user->id, 'event_id' => $request->event_id])->first();

        if ($type === 'undo') {
            $hasCount = $team->followers()->where(['follower_id' => $user->id, 'event_id' => $request->event_id])->count();
            if($team->public_profile || $hasCount){
                $team->followers()->where(['follower_id' => $user->id, 'event_id' => $request->event_id])->delete();
                $team->followerRequests()->where(['prospective_follower_id'=>$user->id,'event_id' => $request->event_id])->delete();
                return $this->sendResponse([], 'Follow request undone');
            }

            if (! is_null($followRequest)) {
                $followRequest->delete();

                return $this->sendResponse([], 'Follow request undone');
            }

            return $this->sendError('ERROR', ['error' => 'Follow request does not exist']);
        }

        if (! is_null($followRequest)) {
            return $this->sendError('ERROR', ['error' => 'Follow request already exists']);
        }

        if ($team->public_profile) {
            $team->followers()->where(['follower_id' => $user->id, 'event_id' => $request->event_id])->delete();
            $team->followers()->create(['follower_id' => $user->id, 'event_id' => $request->event_id]);

            return $this->sendResponse([], 'Following');
        }

        $team->followerRequests()->updateOrcreate(['prospective_follower_id' => $user->id, 'event_id' => $request->event_id], ['prospective_follower_id' => $user->id, 'event_id' => $request->event_id, 'status' => 'request_to_follow_issued']);

        return $this->sendResponse([], 'Follow requested');
    }

    public function teamFollowing(Request $request, $type = null): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();
        $page = $request->page ?? 1;

        $cacheName = "user_team_following_{$user->id}_{$page}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);
            // return $this->sendResponse($item, 'Response');
        }

        $followings = $this->teamService->following($user, $request->event_id, $page);

        return $this->sendResponse($followings, 'Response');
    }

    public function teamFollowRequests(Request $request, $type = null): JsonResponse
    {

        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();

        $page = $request->page ?? 1;
        $cacheName = "user_team_follow_request_{$user->id}_{$page}";

        // /if(Cache::has($cacheName)){
        //    $item = Cache::get($cacheName);
        // return $this->sendResponse($item, 'Response');
        //           }

        $followings = TeamFollowRequest::whereNotIn('status', ['request_to_follow_approved', 'request_to_follow_ignored'])->whereHas('team', function ($query) use ($user, $request) {
            return $query->where('event_id', $request->event_id)->where('owner_id', $user->id);
        })
            ->with('user', function ($query) {
                return $query->select(['id', 'first_name', 'last_name', 'display_name']);
            })
            ->with('team', function ($query) use ($user, $request) {
                return $query->where('event_id', $request->event_id)->where('owner_id', $user->id);
            })->simplePaginate(100);
        // dd($user->teams()->where('event_id',$request->event_id)->where('followerRequests')->get());

        // $followings = $user->teamFollowingRequests()->where('event_id', $request->event_id)->with('team')->simplePaginate(100);
        // Cache::put($cacheName, $followings, now()->addHours(2));
        return $this->sendResponse($followings, 'Response');
    }

    private function decodeModalities($sum)
    {
        $decoded = [];

        $modalities = [
            'daily_steps' => 1,
            'run' => 2,
            'walk' => 4,
            'bike' => 8,
            'swim' => 16,
            'other' => 32,
        ];

        foreach ($modalities as $key => $value) {
            if (($sum & $value) !== 0) {
                $decoded[] = $key;
            }
        }

        return $decoded;
    }
}
