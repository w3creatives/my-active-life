<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Interfaces\DataSourceInterface;
use App\Models\DataSource;
use App\Models\Event;
use App\Models\FitLifeActivityRegistration;
use App\Models\PointMonthly;
use App\Models\PointTotal;
use App\Models\Team;
use App\Models\User;
use App\Models\UserPoint;
use App\Services\EventService;
use App\Services\GarminService;
use App\Services\MilestoneImageService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Log;

final class UserPointsController extends BaseController
{
    public function listView(Request $request): JsonResponse
    {

        $isCalendarMode = $request->mode === 'calendar';

        $request->validate([
            'mode' => 'required|in:list,calendar',
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'start_date' => [
                Rule::requiredIf($isCalendarMode),
                'date',
            ],
            'end_date' => [
                Rule::requiredIf($isCalendarMode),
                'date',
            ],
        ]);

        $pageLimit = $request->page_limit ?? 100;

        $user = $request->user();

        $totalPoint = $user->totalPoints()->where('event_id', $request->event_id)->first();

        $cumulativeMile = $totalPoint ? $totalPoint->amount : 0;

        $pageNum = $request->page ?? 1;

        $cacheName = "team_{$user->id}_{$request->event_id}_{$request->start_date}_{$request->end_date}_{$pageLimit}_$pageNum";

        // if(Cache::has($cacheName)){
        // $item = Cache::get($cacheName);
        // return $this->sendResponse($item, 'Response');
        // }

        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $points = $user->points()
            ->selectRaw('SUM(amount) as total_mile,date, note')
            ->where(function ($query) use ($startDate, $endDate) {
                // if($isCalendarMode) {
                if ($startDate && $endDate) {
                    return $query->where('date', '<=', $endDate)->where('date', '>=', $startDate);
                }

                return $query;
            })
            ->where('event_id', $request->event_id)->groupBy(['date', 'note'])
            ->simplePaginate($pageLimit);

        $points->through(function ($item) use ($event, $user) {
            $item->cumulative_mile = $user->points()->where('event_id', $event->id)
                ->where('date', '>=', $event->start_date)
                ->where('date', '<=', $item->date)->sum('amount');
            // $item->note = $participation->note;
            $item->milestone = $event->milestones()->where('distance', '<=', $item->cumulative_mile)->orderBy('distance', 'DESC')->first();

            return $item;
        });

        $points->through(function ($item, $key) use ($event, $user, $points) {

            if ($event->event_type === 'promotional') {
                $userStreak = $user->userStreaks()->where('event_id', $item->id)->where('date', $item->date)->first();

                if (is_null($userStreak) || ! $userStreak->streak) {
                    $item->milestone = null;

                    return $item;
                }

                $milestone = $userStreak->streak;

                $milestone->image = $milestone->images();

                $item->milestone = $milestone;

                return $item;
            }

            if ($event->event_type === 'fit_life') {

                $milestone = $this->fitLife($user, $event, $item);

                if ($milestone) {
                    $milestoneTotalPoints = $milestone->total_points;

                    $milestone->is_completed = false;

                    if ($milestoneTotalPoints > 0) {
                        $milestone->is_completed = $item->total_mile >= $milestoneTotalPoints;
                    }
                    $milestone->image = $milestone->images($milestone->is_completed);
                    /**
                     * Deprecated since new version
                     */
                    // $milestone->image = $this->milestoneImages($event, $milestone->total_points, $milestone->activity_id, $milestone->is_completed);
                }

                $item->milestone = $milestone;

                $item->bibs_url = null;

                return $item;
            }

            $prevItem = $points->get($key - 1);

            $milestone = $event->milestones()->selectRaw('name,description,distance,data,logo, team_logo, calendar_logo, calendar_team_logo');

            if ($prevItem) {
                $milestone = $milestone->where('distance', '<=', $item->cumulative_mile)->where('distance', '>', $prevItem->cumulative_mile);
            } else {
                $milestone = $milestone->where('distance', '<=', $item->cumulative_mile);
            }

            $milestone = $milestone->orderBy('distance', 'DESC')->first();

            // if(is_null($milestone)) {
            // $milestone = $event->milestones()->selectRaw('name,description,distance,data')->where('distance','<=', $item->total_mile)->orderBy('distance','DESC')->first();
            // }

            $bibs_url = null;

            if ($milestone) {
                if ($event->bibs_name) {
                    $bibs_url = sprintf('https://bibs.runtheedge.com/?miles=%s-%s', $event->bibs_name, $milestone->distance);
                }

                $milestone->image = $milestone->images();

                /**
                 * Deprecated since new version
                 */
                // $milestone->image = $this->milestoneImages($event, $milestone->distance);
            }
            $item->bibs_url = $bibs_url;
            $item->milestone = collect($milestone)->except(['logo', 'team_logo', 'calendar_logo', 'calendar_team_logo']);

            // https://staging-tracker.runtheedge.com/api/v1/event_milestone_images?event_id=2&distance=2832

            return $item;
        });

        // Cache::put($cacheName, compact('points','participation'), now()->addHours(2));
        return $this->sendResponse(compact('points', 'participation'), 'Response');
    }

    public function unittestListView(Request $request): JsonResponse
    {

        $isCalendarMode = $request->mode === 'calendar';

        $request->validate([
            'mode' => 'required|in:list,calendar',
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'start_date' => [
                Rule::requiredIf($isCalendarMode),
                'date',
            ],
            'end_date' => [
                Rule::requiredIf($isCalendarMode),
                'date',
            ],
        ]);

        $pageLimit = $request->page_limit ?? 100;

        $user = User::find($request->user_id);

        $totalPoint = $user->totalPoints()->where('event_id', $request->event_id)->first();

        // $cumulativeMile = $totalPoint?$totalPoint->amount:0;

        $pageNum = $request->page ?? 1;

        $cacheName = "team_{$user->id}_{$request->event_id}_{$request->start_date}_{$request->end_date}_{$pageLimit}_$pageNum";

        // if(Cache::has($cacheName)){
        // $item = Cache::get($cacheName);
        // return $this->sendResponse($item, 'Response');
        // }

        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $points = $user->points()
            ->selectRaw('SUM(amount) as total_mile,date, note')
            ->where(function ($query) use ($startDate, $endDate) {
                // if($isCalendarMode) {
                if ($startDate && $endDate) {
                    return $query->where('date', '<=', $endDate)->where('date', '>=', $startDate);
                }

                return $query;
            })
            ->where('event_id', $request->event_id)->groupBy(['date', 'note'])
            ->simplePaginate($pageLimit);
        $st = microtime(true);
        $points->through(function ($item) use ($event, $user) {

            $item->cumulative_mile = $user->points()->selectRaw('SUM(amount) as cumulative_mile')->where('event_id', $event->id)->where('date', '<=', $item->date)->pluck('cumulative_mile')->first(); // ->sum('amount');

            // $item->note = $participation->note;
            // $item->milestone = $event->milestones()->where('distance','<=', $item->cumulative_mile)->orderBy('distance','DESC')->first();

            return $item;
        });

        $et = microtime(true);

        /*

   $points->through(function($item, $key) use($event, $user, $points){

       if($event->event_type == 'fit_life') {
            $milestone = $this->fitLife($user,$event,$item);

             if($milestone){
                   $milestone->image = null;//$this->milestoneImages($event->id,$milestone->total_points, $milestone->activity_id);
               }

            $item->milestone = $milestone;

            return $item;
       }

       $prevItem = $points->get($key-1);

       if($prevItem) {

            $milestone =$event->milestones()->selectRaw('name,description,distance,data')->where('distance','<=', $item->cumulative_mile)->where('distance','>',$prevItem->cumulative_mile)->orderBy('distance','DESC')->first();
       } else {
           $milestone = $event->milestones()->selectRaw('name,description,distance,data')->where('distance', $item->cumulative_mile)->orderBy('distance','DESC')->first();
       }
       if($milestone){
           $milestone->image = null;//$this->milestoneImages($event->id,$milestone->distance);
       }

       $item->milestone = $milestone;

       //https://staging-tracker.runtheedge.com/api/v1/event_milestone_images?event_id=2&distance=2832

       return $item;
   });

  */
        // Cache::put($cacheName, compact('points','participation'), now()->addHours(2));
        return $this->sendResponse(compact('points', 'participation'), 'Response');
    }

    public function testlistView(Request $request): JsonResponse
    {

        $isCalendarMode = $request->mode === 'calendar';

        $request->validate([
            'mode' => 'required|in:list,calendar',

            'start_date' => [
                Rule::requiredIf($isCalendarMode),
                'date',
            ],
            'end_date' => [
                Rule::requiredIf($isCalendarMode),
                'date',
            ],
        ]);

        $pageLimit = $request->page_limit ?? 20000;

        $user = User::find($request->user_id);

        $pageNum = $request->page ?? 1;

        $skipMilestone = $request->skip_milestone === true;

        $cacheName = "team_{$user->id}_{$request->event_id}_{$request->start_date}_{$request->end_date}_{$pageLimit}_$pageNum";

        if (Cache::has($cacheName)) {
            // $item = Cache::get($cacheName);
            // return $this->sendResponse($item, 'Response');
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $points = $user->points()
            ->selectRaw('SUM(amount) as total_mile,date, note,event_id')
            ->where(function ($query) use ($startDate, $endDate) {
                // if($isCalendarMode) {
                if ($startDate && $endDate) {
                    return $query->where('date', '<=', $endDate)->where('date', '>=', $startDate);
                }

                return $query;
            })
            // ->where('event_id',$request->event_id)
            ->groupBy(['date', 'note', 'event_id'])
            ->simplePaginate($pageLimit);
        if (! $skipMilestone) {
            $points->through(function ($item) {
                $event = $item->event;
                $item->cumulative_mile = $item->total_mile; // $user->points()

                // ->selectRaw('SUM(amount) as total_mile')
                // ->where('event_id',$event->id)->where('date','<=',$item->date)->sum('amount');
                // $item->note = $participation->note;
                // $item->milestone = $event->milestones()->where('distance','<=', $item->cumulative_mile)->orderBy('distance','DESC')->first();
                return $item;
            });

            $points->through(function ($item, $key) use ($user, $points) {
                $event = $item->event;
                if ($event->event_type === 'fit_life') {
                    $milestone = $this->fitLife($user, $event, $item);

                    if ($milestone) {
                        $milestone->image = $this->milestoneImages($event, $milestone->total_points, $milestone->activity_id);
                    }

                    $item->milestone = $milestone;

                    return $item;
                }

                $prevItem = $points->get($key - 1);

                $milestone = $event->milestones()->selectRaw('name,description,distance,data');

                if ($prevItem) {
                    $prevItem->cumulative_mile;
                    $milestone = $milestone->where('distance', '<=', $item->cumulative_mile)->where('distance', '>', $prevItem->cumulative_mile);
                } else {
                    $milestone = $milestone->where('distance', $item->cumulative_mile);
                }

                $milestone = $milestone->orderBy('distance', 'DESC')->first();

                if ($milestone) {
                    $milestone->image = $this->milestoneImages($event, $milestone->distance);
                }

                $item->milestone = $milestone;

                // https://staging-tracker.runtheedge.com/api/v1/event_milestone_images?event_id=2&distance=2832

                return $item;
            });
        }

        // Cache::put($cacheName, compact('points','participation'), now()->addHours(2));
        return $this->sendResponse(compact('points'), 'Response');
    }

    public function viewPoint(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'date' => [
                'required',
                'date',
            ],
        ]);

        $user = $request->user();

        $cacheName = "team_{$user->id}_{$request->event_id}_{$request->date}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);
            // return $this->sendResponse($item, 'Response');
        }

        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        $points = $user->points()
            // ->select(['amount','data_source_id','modality'])
            ->where('date', $request->date)
            ->where('event_id', $request->event_id)
            ->get();

        if (! $points->count()) {
            return $this->sendError('ERROR', ['error' => 'No data found']);
        }

        $data = [];

        $note = null;

        foreach ($points as $point) {
            $note = $point->note;
            $data[] = [
                'id' => $point->id,
                'modality' => $point->modality,
                'amount' => $point->amount,
                'data_source_id' => $point->data_source_id,
            ];
        }

        $item = $points->first()->only(['date', 'event_id', 'transaction_id']);

        $item['note'] = $note; // $participation->note;

        $item['points'] = $data;
        Cache::put($cacheName, $item, now()->addHours(2));

        return $this->sendResponse($item, 'Response');
    }

    public function list(Request $request): JsonResponse
    {

        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();

        $eventId = $request->event_id;

        $today = Carbon::now()->format('Y-m-d');

        $cacheName = "team_{$user->id}_{$request->event_id}_{$today}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);
            // return $this->sendResponse($item, 'Response');
        }

        $event = Event::find($eventId);

        $achievements = $user->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get()->groupBy('achievement');

        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

        $dayPoint = $user->points()->where('event_id', $eventId)->where('date', $today)->sum('amount');
        $weekPoint = $user->points()->where('event_id', $eventId)->where('date', '>=', $startOfWeek)->where('date', '<=', $endOfWeek)->sum('amount');
        $monthPoint = $user->points()->where('event_id', $eventId)->where('date', '>=', $startOfMonth)->where('date', '<=', $endOfMonth)->sum('amount');

        $achievements = $user->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get();

        $yearwisePoints = $user->points()->selectRaw('SUM(amount) miles,extract(year from "date") as year')->where('event_id', $eventId)->groupBy('year')->orderBy('year', 'DESC')->get();

        $totalPoints = $user->points()->where('event_id', $eventId)->sum('amount');

        $achievementData = [
            'best_day' => [
                'achievement' => 'best_day',
                'accomplishment' => null,
                'date' => null,
            ],
            'best_week' => [
                'achievement' => 'best_week',
                'accomplishment' => null,
                'date' => null,
            ],
            'best_month' => [
                'achievement' => 'best_month',
                'accomplishment' => null,
                'date' => null,
            ],
        ];

        if ($achievements->count()) {
            foreach ($achievements as $achievement) {
                $achievementData[$achievement->achievement]['accomplishment'] = $achievement->accomplishment;
                $achievementData[$achievement->achievement]['date'] = $achievement->date;
            }
        }

        $achievementData['current_day'] = [
            'achievement' => 'day',
            'accomplishment' => $dayPoint,
            'date' => $today,
        ];

        $achievementData['current_week'] = [
            'achievement' => 'week',
            'accomplishment' => $weekPoint,
            'date' => $endOfWeek,
        ];

        $achievementData['current_month'] = [
            'achievement' => 'month',
            'accomplishment' => $monthPoint,
            'date' => $endOfMonth,
        ];

        $data = [
            'event' => $event,
            'achievement' => $achievementData,
            'miles' => [
                'total' => $totalPoints,
                'chart' => $yearwisePoints,
            ],
        ];

        // dd($data);
        Cache::put($cacheName, compact('data'), now()->addHours(2));

        return $this->sendResponse(compact('data'),
            'Response');

        return $points;

    }

    public function index(Request $request): JsonResponse
    {

        $request->validate([
            'start_date' => 'required',
            'end_date' => 'required',
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            // "modality" =>  "in:run,walk,bike,swim,other"
        ]);

        $user = $request->user();

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $modality = $request->modality;
        $eventId = $request->event_id;

        $cacheName = "team_{$user->id}_{$request->event_id}_{$startDate}_{$endDate}_{$modality}";

        if (Cache::has($cacheName)) {
            $item = Cache::get($cacheName);
            //  return $this->sendResponse($item, 'Response');
        }

        $event = Event::find($request->event_id);

        $milestones = $event->milestones()->get();

        $participations = $user->participations()->with('event')->get()->each(function ($participation) {
            if ($participation->event) { // Check if event exists
                $participation->event['supported_modalities'] = $this->decodeModalities($participation->event['supported_modalities']);
            }
        });

        $points = $user->points()->with(['event'])
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where(function ($query) use ($modality, $eventId) {

                if ($modality) {
                    $query->where('modality', $modality);
                }

                if ($eventId) {
                    $query->where('event_id', $eventId);
                }

                return $query;
            })
            ->get();

        Cache::put($cacheName, compact('points', 'participations', 'milestones'), now()->addHours(2));

        return $this->sendResponse(compact('points', 'participations', 'milestones'),
            'Response');
    }

    public function store(Request $request, EventService $eventService, $id = null): JsonResponse
    {

        $user = $request->user();

        $settings = json_decode($user->settings, true);

        $manualEntryEnabled = (isset($settings['manual_entry_populates_all_events']) && $settings['manual_entry_populates_all_events'] === true);

        $request->validate([
            'amount' => Rule::requiredIf(! $request->points),
            'date' => 'required|date',
            // "event_id" =>  "required",
            'note' => 'max:100',
            'event_id' => [
                Rule::requiredIf(is_null($id)),
                Rule::exists(Event::class, 'id'),
            ],
            'points.*.modality' => 'required|in:run,walk,bike,swim,other,daily_steps',
            'points.*.data_source_id' => [
                'required',
                Rule::exists((new DataSource)->getTable(), 'id'),
            ],
            'points.*.amount' => 'required|numeric',
        ]);

        if ($id) {

            $userPoint = $user->points()->find($id);

            if (! $userPoint) {
                return $this->sendError('Invalid Event ID.', ['error' => 'User is not participated in this event']);
            }

            $userPoint->update($request->only(['amount', 'date', 'modality', 'note']));

            $eventService->createOrUpdateUserPoint($user, $request->event_id, $request->date);
            $eventService->userPointWorkflow($user->id, $request->event_id);

            return $this->sendResponse([], 'User Points updated');
        }

        $hasUserEventParticipation = $user->participations()->where('event_id', $request->event_id)->count();

        if (! $hasUserEventParticipation) {
            return $this->sendError('Invalid Event ID.', ['error' => 'User is not participated in this event']);
        }

        $currentDate = Carbon::now()->format('Y-m-d');

        $eventId = $request->event_id;

        $participations = $user->participations()->where('subscription_end_date', '>=', $currentDate)->whereHas('event', function ($query) use ($manualEntryEnabled, $currentDate, $eventId) {

            if ($manualEntryEnabled) {
                return $query->where('start_date', '<=', $currentDate);
            }

            return $query->where('start_date', '<=', $currentDate)->where('id', $eventId);

        })->get();
        // dd($participations);
        if (! $participations->count()) {
            return $this->sendError('Invalid Event ID.', ['error' => 'User is not participated in this event']);
        }
        //  dd(Event::find($request->event_id), $user->participations()->where('event_id', $request->event_id)->first(),$participations->pluck('event_id'));
        foreach ($participations as $participation) {

            foreach ($request->points as $point) {

                $data = ['amount' => $point['amount'], 'date' => $request->date, 'event_id' => $participation->event_id, 'modality' => $point['modality'], 'data_source_id' => $point['data_source_id'], 'note' => $request->note];

                $userPoint = $user->points()->where(['date' => $request->date, 'modality' => $point['modality'], 'event_id' => $participation->event_id, 'data_source_id' => $point['data_source_id']])->first();

                if ($userPoint) {
                    $userPoint->update($data);

                    continue;
                }

                $user->points()->create($data);
            }

            $this->questMilestoneAcheivement($user, $request->date, $participation->event_id);

            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $request->date);
            $eventService->userPointWorkflow($user->id, $participation->event_id);
        }

        // $userParticipation = $user->participations()->where('event_id', $request->event_id)->first();

        // $userParticipation->fill(['note' => $request->note])->save();

        return $this->sendResponse([], 'User Points updated');

        $data = $request->only(['amount', 'date', 'event_id', 'modality']);

        $data['data_source_id'] = 5;
        $data['transaction_id'] = null;

        $userPoint = $user->points()->where(['date' => $request->date, 'modality' => $request->modality])->first();

        if ($userPoint) {
            $userPoint->update($data);
            $eventService->createOrUpdateUserPoint($user, $request->event_id, $request->date);
            $this->questMilestoneAcheivement($user, $request->date, $request->event_id);

            return $this->sendResponse([], 'User Points updated');
        }

        $user->points()->create($data);

        $this->questMilestoneAcheivement($user, $request->date, $request->event_id);
        $eventService->createOrUpdateUserPoint($user, $request->event_id, $request->date);

        return $this->sendResponse([], 'User Points added');

    }

    public function updatePoints(Request $request, EventService $eventService): JsonResponse
    {

        $user = $request->user();

        $request->validate([
            'note' => 'max:100',
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'points.*.point_id' => [
                'required',
                Rule::exists(UserPoint::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($user) {

                    $hasPoint = $user->points()->find($value);

                    if (! $hasPoint) {
                        $fail("Point ID {$value} does not belong to user activity");

                        return false;
                    }

                    return true;
                },
            ],
            'points.*.amount' => 'required|numeric',
        ]);

        foreach ($request->points as $point) {

            $userPoint = $user->points()->find($point['point_id']);

            $userPoint->update(['amount' => $point['amount'], 'note' => $request->note]);

            $eventService->createOrUpdateUserPoint($user, $request->event_id, $request->date);

        }
        $eventService->userPointWorkflow($user->id, $request->event_id);

        return $this->sendResponse([], 'User Points updated');
    }

    public function membershipInvites(Request $request): JsonResponse
    {

        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();

        $eventId = $request->event_id;

        $membershipRequests = Team::whereHas('invites', function ($query) use ($user, $eventId) {
            return $query->where('prospective_member_id', $user->id)->where('event_id', $eventId);
        })->get();

        return $this->sendResponse($membershipRequests, 'Response');
    }

    public function membershipInviteAction(Request $request, $type): JsonResponse
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

        if (! in_array($type, ['accept', 'decline'])) {
            return $this->sendError('Invalid Request', ['error' => 'Invalid Request']);
        }

        $user = $request->user();

        $eventId = $request->event_id;

        $membershipRequest = $user->invites()->where(['team_id' => $request->team_id, 'event_id' => $request->event_id])->first();

        if (is_null($membershipRequest)) {
            return $this->sendError('Team membership request not found', ['error' => 'Team membership request not found']);
        }

        $team = $membershipRequest->team;

        if (is_null($team)) {
            return $this->sendError('Team not found', ['error' => 'Team not found']);
        }

        if ($type === 'accept') {

            $hasMembership = $team->memberships()->where(['event_id' => $request->event_id, 'user_id' => $user->id])->count();

            if ($hasMembership) {
                return $this->sendError('NOT FOUND', ['error' => 'Invalid Request']);
            }
            $membershipRequest->delete();
            $team->memberships()->create(['event_id' => $request->event_id, 'user_id' => $user->id]);

            return $this->sendResponse([], 'Team invitation accepted');
        }

        $membershipRequest->delete();

        return $this->sendResponse([], 'Team invitation declined');
    }

    public function syncPoints(Request $request, EventService $eventService, GarminService $garminService)
    {
        $request->validate([
            'sync_start_date' => [
                'required',
                'date_format :Y-m-d',
            ],
            'data_source' => [
                'required',
                Rule::exists((new DataSource)->getTable(), 'short_name'),
            ],
        ]);

        $user = $request->user();

        $sourceSlug = $request->data_source;

        $sourceProfile = $user->profiles()->whereHas('source', function ($query) use ($sourceSlug) {
            return $query->where('short_name', $sourceSlug);
        })->first();

        if (is_null($sourceProfile)) {
            return $this->sendError('ERROR', ['error' => 'Source profile not found']);
        }

        $isGarmin = $sourceSlug === 'garmin';

        $startDate = $request->get('sync_start_date');

        $tracker = app(DataSourceInterface::class);

        $activities = $tracker->get($sourceSlug)
            ->setSecrets([$sourceProfile->access_token, $sourceProfile->access_token_secret])
            ->setDate($startDate, Carbon::now()->format('Y-m-d'))
            ->activities($isGarmin ? 'response' : 'data');

        if ($isGarmin) {
            $response = $activities->first();

            if ($response->status() === 202) {
                return $this->sendResponse(['sync_start_date' => $startDate], 'Your data will be processed shortly.');
            }

            if ($response->status() === 409) {
                return $this->sendResponse($response->json(), "We've already processed your data.");
            }
        }

        if ($activities->count()) {
            foreach ($activities as $activity) {
                $activity['dataSourceId'] = $sourceProfile->data_source_id;
                $eventService->createUserParticipationPoints($user, $activity);
            }
        }

        return $this->sendResponse(['sync_start_date' => $startDate], 'User Points added');

        /**
         * Deprecated beyond this
         */

        /**
         * if($sourceProfile->source->short_name !== 'fitbit'){
         * return $this->sendError('ERROR', ['error'=>'Only Fitbit is supported for now']);
         * }
         */
        switch ($request->data_source) {
            case 'fitbit':
                return $this->syncFitbitPoints($sourceProfile, $request, $eventService);

            case 'garmin':
                return $this->syncGarminPoints($sourceProfile, $request, $eventService, $garminService);

            case 'strava':
                return $this->syncStravaPoints($sourceProfile, $request, $eventService);

            default:
                return $this->sendError('ERROR', ['error' => 'Unsupported data source']);
        }
    }

    // Add at last
    public function profileStats(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
            ],
        ]);

        $user = $request->user();
        $eventId = $request->event_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $event = Event::find($eventId);

        if (! $event) {
            return $this->sendError('Event not found.', [], 404);
        }

        $cacheKeyMothly = "user_monthly_stats_{$user->id}_{$eventId}_{$startDate}_{$endDate}";
        $cacheKeyYearly = "user_yearly_stats_{$user->id}_{$eventId}_{$startDate}_{$endDate}";

        // if($request->clear_cache) {
        Cache::forget($cacheKeyMothly);
        Cache::forget($cacheKeyYearly);
        // }

        if (Cache::has($cacheKeyMothly)) {
            $monthlyPoints = Cache::get($cacheKeyMothly);
        } else {
            $monthlyPoints = PointMonthly::select('amount', 'date')
                ->where('event_id', $eventId)
                ->where('user_id', $user->id)
                ->get();

            Cache::put($cacheKeyMothly, $monthlyPoints, now()->addMonth());
        }

        // Get yearly aggregated data
        $yearlyStats = [];

        if (Cache::has($cacheKeyYearly)) {
            $yearlyStats = Cache::get($cacheKeyYearly);
        } else {
            if (! empty($event->event_group)) {
                $relatedEvents = Event::where('event_group', $event->event_group)
                    ->where('id', '!=', $eventId) // Exclude the current event
                    ->get();

                foreach ($relatedEvents as $relatedEvent) {
                    $relatedEventMonthlyPoints = PointMonthly::select('amount', 'date')
                        ->where('event_id', $relatedEvent->id)
                        ->where('user_id', $user->id)
                        ->get();

                    $eventYearlyData['label'] = $relatedEvent->name;
                    $filledMonths = $this->fillMissingMonths($relatedEventMonthlyPoints);
                    $eventYearlyData['total_miles'] = array_sum(array_column($filledMonths, 'total_miles'));
                    $eventYearlyData['month'] = $filledMonths;

                    $yearlyStats[] = $eventYearlyData;
                    usort($yearlyStats, function ($a, $b) {
                        return strcmp($a['label'], $b['label']);
                    });
                }
            } else {
                $yearlyStats = $this->generateYearlyStats($monthlyPoints);
            }

            Cache::put($cacheKeyYearly, $yearlyStats, now()->addMonth());
        }

        // Structure the response based on number of years
        $response = [
            'monthly_stats' => $monthlyPoints,
            'yearly_stats' => $yearlyStats,
        ];

        return $this->sendResponse($response, 'Profile statistics retrieved successfully');
    }

    // Get the total points for the user for the specified event
    public function totalPoints(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();
        $eventId = $request->event_id;

        $userTotalPoints = PointTotal::where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->value('amount');

        return $this->sendResponse(['total_points' => $userTotalPoints], 'Total points retrieved successfully');
    }

    public function last30DaysStats(Request $request)
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();
        $eventId = $request->event_id;

        // Calculate date range
        $endDate = Carbon::now()->format('Y-m-d');
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');

        // Get daily points for last 30 days
        $dailyPoints = $user->points()
            ->selectRaw('DATE(date) as date, SUM(amount) as daily_total')
            ->where('event_id', $eventId)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Create a collection of all dates in the range
        $dateRange = collect();
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);

        while ($currentDate <= $lastDate) {
            $dateRange->push([
                'date' => $currentDate->format('Y-m-d'),
                'daily_total' => 0,
                'seven_day_avg' => 0,
            ]);
            $currentDate->addDay();
        }

        // Map actual points to date range
        $dailyPoints->each(function ($point) use (&$dateRange) {
            $dateRange = $dateRange->map(function ($item) use ($point) {
                if ($item['date'] === $point->date) {
                    $item['daily_total'] = round($point->daily_total, 2);
                }

                return $item;
            });
        });

        // Calculate 7-day rolling average
        $dateRange = $dateRange->map(function ($item, $key) use ($dateRange) {
            // Get previous 7 days including current day
            $sevenDayWindow = $dateRange->filter(function ($windowItem) use ($item) {
                $itemDate = Carbon::parse($item['date']);
                $windowDate = Carbon::parse($windowItem['date']);

                return $windowDate <= $itemDate &&
                    $windowDate > $itemDate->copy()->subDays(7);
            });

            $sevenDaySum = $sevenDayWindow->sum('daily_total');
            $item['seven_day_avg'] = round($sevenDaySum / min(7, $sevenDayWindow->count()), 2);

            return $item;
        });

        return $this->sendResponse([
            'stats' => $dateRange->values(),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ], 'Last 30 days statistics retrieved successfully');
    }

    public function modalityTotalsByEvent(Request $request)
    {
        $request->validate([
            'event_id' => [
                'nullable',
                Rule::exists(Event::class, 'id'),
            ],
        ]);

        $user = $request->user();
        $eventId = $request->event_id;

        $query = $user->points()
            ->selectRaw('
                event_id,
                modality,
                SUM(amount) as total_amount,
                COUNT(*) as total_entries
            ')
            ->groupBy(['event_id', 'modality']);

        // If event_id is provided, filter for that specific event
        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $modalityTotals = $query->with('event:id,name')
            ->orderBy('event_id')
            ->orderBy('modality')
            ->get()
            ->groupBy('event_id')
            ->map(function ($eventGroup) {
                $event = $eventGroup->first()->event;

                return [
                    'event_name' => $event->name,
                    'event_id' => $event->id,
                    'modalities' => $eventGroup->mapWithKeys(function ($item) {
                        return [$item->modality => [
                            'total_amount' => round($item->total_amount, 2),
                            'total_entries' => $item->total_entries,
                            'average_per_entry' => round($item->total_amount / $item->total_entries, 2),
                        ]];
                    }),
                ];
            })
            ->values();

        return $this->sendResponse([
            'modality_totals' => $modalityTotals,
        ], 'Modality totals retrieved successfully');
    }

    private function milestoneImages($event, $distance, $activityId = null, $isCompleted = false)
    {

        if ($event->event_type === 'fit_life') {
            $imageService = app(MilestoneImageService::class);
            $result = $imageService->getMilestoneImage($event->id, $distance, $activityId);

            if (empty($result)) {
                return [
                    'logo_image_url' => null,
                    'team_logo_image_url' => null,
                    'calendar_logo_image_url' => null,
                    'calendar_team_logo_image_url' => null,
                ];
            }

            if ($isCompleted) {
                return [
                    'logo_image_url' => $result['bib'][1]['url'] ?? null,
                    'team_logo_image_url' => null,
                    'calendar_logo_image_url' => $result['calendar'][1]['url'] ?? null,
                    'calendar_team_logo_image_url' => null,
                ];
            }

            return [
                'logo_image_url' => $result['bib'][0]['url'] ?? null,
                'team_logo_image_url' => null,
                'calendar_logo_image_url' => $result['calendar'][0]['url'] ?? null,
                'calendar_team_logo_image_url' => null,
            ];
        }

        $eventId = $event->id;
        // Get bib image from Ruby
        $formData = [
            'event_id' => $eventId,
            'distance' => $distance,
        ];

        if ($activityId) {
            $formData['activity_id'] = $activityId;
        }

        $response = Http::get('https://staging-tracker.runtheedge.com/api/v1/event_milestone_images', $formData);

        $data = $response->json('data');

        if (! $data) {
            return [];
        }

        return $data[0]['attributes'];
    }

    private function fitLife($user, $event, $item)
    {

        $fitLife = FitLifeActivityRegistration::where('date', $item->date)->where('user_id', $user->id)->whereHas('activity', function ($query) use ($event) {
            return $query->where('event_id', $event->id);
        })->first();

        if (is_null($fitLife) || ! $fitLife->activity) {
            return null;
        }

        return $fitLife->activity->milestones()->where('total_points', '<=', $item->total_mile)->orderBy('total_points', 'desc')->first();
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

    private function questMilestoneAcheivement($user, $date, $eventId)
    {
        $totalPoints = $user->points()->where(['date' => $date, 'event_id' => $eventId])->sum('amount');

        $quest = $user->questRegistrations()->where('date', $date)->whereHas('activity', function ($query) use ($eventId) {
            return $query->where('event_id', $eventId);
        })->first();

        if (is_null($quest)) {
            return false;
        }

        $activity = $quest->activity;

        $milestone = $activity->milestones()->where('total_points', '<=', $totalPoints)->latest('total_points')->first();

        $milestoneIds = $activity->milestones()->where('total_points', '>', $totalPoints)->pluck('id')->toArray();

        if ($milestoneIds) {
            $quest->milestoneStatuses()->whereIn('milestone_id', $milestoneIds)->where(['user_id' => $user->id])->delete();
        }

        if ($milestone) {
            $hasMileStatus = $quest->milestoneStatuses()->where(['milestone_id' => $milestone->id, 'user_id' => $user->id])->count();

            if (! $hasMileStatus) {
                $quest->milestoneStatuses()->create(['milestone_id' => $milestone->id, 'user_id' => $user->id]);
            }
        }

        return true;

        /*DELETE FROM fit_life_activity_milestone_statuses
      WHERE registration_id = #{registration_id}
      	AND milestone_id IN(
      		SELECT
      			id FROM fit_life_activity_milestones
      		WHERE
      			total_points > #{total_points})
    SQL*/
    }

    private function syncFitbitPoints($sourceProfile, $request, $eventService)
    {
        try {
            /*
            $httpClient = new Client([
                'base_uri' => 'https://api.fitbit.com/1/',
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $sourceProfile->access_token),
                    'Accept' => 'application/json',
                ],
            ]);
*/
            $startDate = $request->get('sync_start_date');
            $endDate = Carbon::now()->format('Y-m-d');

            $startDate = CarbonImmutable::parse($startDate);
            $endDate = $endDate ? CarbonImmutable::parse($endDate) : $startDate;
            $dateDays = $startDate->diffInDays($endDate, true);

            for ($day = 0; $day <= $dateDays; $day++) {
                $totalDistance = 0;

                $activities = $this->findActivities($sourceProfile->access_token, $startDate->addDays($day)->format('Y-m-d'));

                if (! $activities) {
                    continue;
                }

                foreach ($activities as $activity) {
                    $this->createPoints($eventService, $request->user(), $activity['date'], $activity['distance'], $sourceProfile, $activity['modality']);
                }
            }

            return $this->sendResponse(['sync_start_date' => $startDate], 'User Points added');

            /**
             * Deprecated
             */
            $response = $httpClient->get("user/-/activities/distance/date/{$startDate}/{$endDate}.json");

            $dateDistances = json_decode($response->getBody()->getContents(), true)['activities-distance'];

            if (! count($dateDistances)) {
                return $this->sendError('ERROR', ['error' => 'No data found']);
            }

            foreach ($dateDistances as $data) {
                $distance = $data['value'];
                $date = $data['dateTime'];
                /*
                $this->createOrUpdateUserProfilePoint($profile->user,$distance,$date,$profile,'cron','manual');
                if(!$distance) {
                    $distance = 0;
                }
                */
                $distance = $distance * 0.621371;
                try {
                    $this->createPoints($eventService, $request->user(), $date, $distance, $sourceProfile);
                } catch (Exception $e) {
                }
            }

            return $this->sendResponse(['sync_start_date' => $startDate], 'User Points added');
        } catch (Exception $e) {
            return $this->sendError('ERROR', ['error' => 'Unable to handle your request']);
        }
    }

    private function syncGarminPoints($sourceProfile, $request, $eventService, $garminService)
    {
        try {
            $startDate = $request->get('sync_start_date');
            $endDate = Carbon::now()->format('Y-m-d');

            $response = $garminService->processBackfillDailies(
                $startDate,
                $endDate,
                $sourceProfile->access_token,
                $sourceProfile->access_token_secret
            );

            if ($response->status() === 202) {
                return $this->sendResponse(['sync_start_date' => $startDate], 'Your data will be processed shortly.');
            }

            if ($response->status() === 409) {
                return $this->sendResponse($response->json(), "We've already processed your data.");
            }
        } catch (\Exception $e) {
            Log::error('Garmin sync error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('ERROR', ['error' => 'Unable to process Garmin sync: '.$e->getMessage()]);
        }
    }

    /**
     * Sync Strava activities and insert them into the database
     *
     * @param  object  $sourceProfile  User's Strava profile
     * @param  Request  $request  The HTTP request
     * @param  EventService  $eventService  Event service instance
     * @return JsonResponse
     */
    private function syncStravaPoints($sourceProfile, $request, $eventService)
    {
        try {
            $startDate = $request->get('sync_start_date');
            $endDate = Carbon::now()->format('Y-m-d');

            // Check if token is expired and refresh if needed
            if (Carbon::parse($sourceProfile->token_expires_at)->lt(Carbon::now())) {
                $refreshed = $this->refreshStravaToken($sourceProfile);
                if (! $refreshed) {
                    return $this->sendError('ERROR', ['error' => 'Failed to refresh Strava access token']);
                }
            }

            // Initialize Strava service with the access token
            $stravaService = new \App\Services\StravaService($sourceProfile->access_token);

            // Get activities from Strava API
            $activities = $stravaService->getActivities($startDate, $endDate);

            // dd($activities);

            if (empty($activities)) {
                return $this->sendError('ERROR', ['error' => 'No Strava activities found for the specified date range']);
            }

            $processedCount = 0;

            // Process each activity
            foreach ($activities as $activity) {
                $date = Carbon::parse($activity['start_date_local'])->format('Y-m-d');

                // Convert meters to miles
                $distance = $activity['distance'] * 0.000621371; // Convert meters to miles

                try {
                    // Create points for this activity
                    $this->createPoints($eventService, $request->user(), $date, $distance, $sourceProfile);
                    $processedCount++;
                } catch (\Exception $e) {
                    Log::error('Error creating points for Strava activity', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'activity' => $activity,
                    ]);
                }
            }

            return $this->sendResponse(
                [
                    'sync_start_date' => $startDate,
                    'activities_processed' => $processedCount,
                ],
                'Strava activities synced successfully'
            );
        } catch (\Exception $e) {
            Log::error('Strava sync error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('ERROR', ['error' => 'Unable to process Strava activities: '.$e->getMessage()]);
        }
    }

    /**
     * Refresh Strava access token
     *
     * @param  object  $profile  User's Strava profile
     * @return bool
     */
    private function refreshStravaToken($profile)
    {
        try {
            $response = Http::post('https://www.strava.com/oauth/token', [
                'client_id' => config('services.strava.client_id'),
                'client_secret' => config('services.strava.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $profile->refresh_token,
            ]);

            if (! $response->successful()) {
                Log::error('Failed to refresh Strava token', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return false;
            }

            $data = $response->json();

            $profile->access_token = $data['access_token'];
            $profile->refresh_token = $data['refresh_token'];
            $profile->token_expires_at = Carbon::createFromTimestamp($data['expires_at']);
            $profile->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Exception while refreshing Strava token', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    private function createPoints($eventService, $user, $date, $distance, $sourceProfile, $modality = 'other')
    {

        if (! $distance) {
            return false;
        }

        $currentDate = Carbon::now()->format('Y-m-d');

        $participations = $user->participations()->where('subscription_end_date', '>=', $currentDate)->whereHas('event', function ($query) use ($currentDate) {
            return $query->where('start_date', '<=', $currentDate);
        })->get();

        foreach ($participations as $participation) {

            if (! $participation->isModalityOverridden($modality)) {
                continue;
            }

            $pointdata = ['amount' => $distance, 'date' => $date, 'event_id' => $participation->event_id, 'modality' => $modality, 'data_source_id' => $sourceProfile->data_source_id];

            $userPoint = $user->points()->where(['date' => $date, 'modality' => $modality, 'event_id' => $participation->event_id, 'data_source_id' => $sourceProfile->data_source_id])->first();

            if ($userPoint) {
                $userPoint->update($pointdata);
            } else {
                $user->points()->create($pointdata);
            }

            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $date);
            $eventService->userPointWorkflow($user->id, $participation->event_id);
        }

        return true;
    }

    private function fillMissingMonths($monthlyPoints): array
    {
        if ($monthlyPoints->isEmpty()) {
            return [];
        }

        $minDate = Carbon::parse($monthlyPoints->min('date'))->startOfYear();
        $maxDate = Carbon::parse($monthlyPoints->max('date'))->endOfYear();

        $allDates = [];
        $currentDate = $minDate->copy();

        while ($currentDate->lte($maxDate)) {
            $allDates[] = $currentDate->copy();
            $currentDate->addMonth();
        }

        $filledPoints = [];

        foreach ($allDates as $date) {
            $found = false;
            foreach ($monthlyPoints as $point) {
                if ($date->isSameMonth(Carbon::parse($point->date))) {
                    $filledPoints[] = ['month' => $date->format('M'), 'total_miles' => $point->amount];
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $filledPoints[] = ['month' => $date->format('M'), 'total_miles' => 0];
            }
        }

        return $filledPoints;
    }

    private function generateYearlyStats($monthlyPoints): array
    {
        $yearlyData = [];
        $yearlyTotals = [];

        foreach ($monthlyPoints as $point) {
            $year = date('Y', strtotime($point->date));
            $month = date('n', strtotime($point->date)) - 1; // 0-indexed month

            if (! isset($yearlyData[$year])) {
                $yearlyData[$year] = [
                    'label' => (int) $year,
                    'total_miles' => 0,
                    'month' => [],
                ];
            }

            $yearlyData[$year]['month'][] = [
                'month' => date('M', strtotime($point->date)),
                'total_miles' => (float) $point->amount,
            ];

            if (! isset($yearlyTotals[$year])) {
                $yearlyTotals[$year] = 0;
            }
            $yearlyTotals[$year] += (float) $point->amount;
        }

        $result = [];

        foreach ($yearlyData as $year => $data) {
            $data['total_miles'] = $yearlyTotals[$year];
            $result[] = $data;
        }

        return array_values($result);
    }

    private function findActivities($accessToken, $date): array
    {
        $httpClient = new Client([
            'base_uri' => 'https://api.fitbit.com/1/',
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $accessToken),
                'Accept' => 'application/json',
            ],
        ]);

        $response = $httpClient->get("user/-/activities/date/{$date}.json");

        $result = json_decode($response->getBody()->getContents(), true);

        $activities = collect($result['activities']);
        $distances = collect($result['summary']['distances']);

        $totalDistance = $distances->filter(function ($distance) {
            return $distance['activity'] === 'total';
        })->sum('distance');

        $loggedDistance = $distances->filter(function ($distance) {
            return $distance['activity'] === 'loggedActivities';
        })->sum('distance');

        $otherDistance = $totalDistance - $loggedDistance;

        $fitbitMileConversion = 0.621371;

        $activities = $activities->map(function ($item) use ($fitbitMileConversion) {
            try {
                $modality = $this->modality($item['name']);
                $date = $item['startDate'];
                $distance = $item['distance'] * $fitbitMileConversion;
                $raw_distance = $item['distance'];

                return compact('date', 'distance', 'modality', 'raw_distance');
            } catch (\Exception $e) {
                Log::debug('Fitbit Activity Error : ', ['item' => $item, 'error' => $e->getMessage()]);
            }
        })->reject(function ($item) {
            return $item === null;
        });

        if ($otherDistance > 0) {
            $activities = $activities->push(['date' => $date, 'distance' => $otherDistance * $fitbitMileConversion, 'modality' => 'other', 'raw_distance' => $otherDistance]);
        }

        $items = $activities->reduce(function ($data, $item) {
            if (! isset($data[$item['modality']])) {
                $data[$item['modality']] = $item;

                return $data;
            }

            $data[$item['modality']]['distance'] += $item['distance'];
            $data[$item['modality']]['raw_distance'] += $item['raw_distance'];

            return $data;
        }, []);

        return collect($items)->values()->toArray();
    }

    private function modality(string $modality): string
    {
        return match ($modality) {
            'Run' => 'run',
            'Walk' => 'walk',
            'Bike', 'Bicycling' => 'bike',
            'Swim' => 'swim',
            'Hike' => 'other',
            default => 'daily_steps',
        };
    }
}
