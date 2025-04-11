<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

use Exception;
use Carbon\Carbon;
use App\Models\{
    Event,
    AmerithonPathDistance,
    Modality,
    EventModality
};
use App\Services\{
    TeamService,
    EventService
};

class EventsController extends BaseController
{
    public function updateEventTemplate(Request $request): JsonResponse
    {
        $request->validate([
            "event_id" => [ 
                'required',
                 Rule::exists((new Event)->getTable(),'id'),
            ],
            'template' => ['required','numeric'],
        ]);
        
        $event = Event::find($request->event_id);
        
         if(is_null($event)){
            return $this->sendError('ERROR', ['error'=>'Event not found']);
        }
        
        $event->template = $request->template;
       
        $event->save();
        
        return $this->sendResponse(['template'=>$event->template], 'Event tempate has been updated');
        
    }
    public function all(Request $request, EventService $eventService): JsonResponse
    {

        $user = $request->user();
        
        $listType = $request->list_type?$request->list_type:'all';
        
        $pageLimit = $request->page_limit??100;
        
        $page = $request->page??1;
        
        $cacheName = "user_event_{$user->id}_{$listType}_{$page}_{$pageLimit}";


  
        $currentDate = Carbon::now()->format('Y-m-d');
        
       


      //  $events = Cache::remember($cacheName, now()->addHours(2), function () use($user, $listType, $pageLimit, $eventService){
            $events = Event::where('event_type', '!=', 'promotional')->where('start_date','<=', $currentDate)->whereHas('participations', function($query) use($user, $currentDate){
                return $query->where('user_id',$user->id);
            })
            ->with('participations', function($query) use($user){
                return $query->where('user_id',$user->id)->where('subscription_end_date','>=',$currentDate);
            })
            ->where(function($query) use($listType){
                
                if($listType == 'all') {
                    return $query;
                }
                
                if($listType == 'active') {
                    return $query->where('end_date','>',Carbon::now()->format('Y-m-d'));
                }
                
                return $query->where('end_date','<',Carbon::now()->format('Y-m-d'));
            })
            ->simplePaginate($pageLimit)
            ->through(function ($event) use($eventService, $user){
                
               $membership = $user->memberships()->where(['event_id' => $event->id])->first();
                
                $team = $membership?$membership->team:null;
                
                if($team) {
                    $team->is_team_owner = $team->owner_id == $user->id;   
                }
            
                $event->has_team = !is_null($membership);
                $event->preferred_team_id = $membership?$team->id:null;
                $event->preferred_team = $membership?$team:null;
                $event->is_expired = !Carbon::parse($event->end_date)->gt(Carbon::now());
                
                $event->statistics = $eventService->userMileStatics($event, $user);
            
                return $event;
            });
      //  });

       return $this->sendResponse($events, 'Response');
    }

    public function findOne(Request $request, TeamService $teamService, EventService $eventService): JsonResponse
    {

       $user = $request->user();
       
       $eventId = $request->route('id');
       
       $cacheName = "user_event_one_{$user->id}_{$eventId}_{$request->date}";
       
       if(Cache::has($cacheName)){
           $event = Cache::get($cacheName);
          // return $this->sendResponse($event, 'Response'); 
       }
       
       try{
           
            $participation = $user->participations()->where('event_id', $eventId)->first();
            $points = $user->points()->where('event_id', $eventId)->where(function($query) use($request){
                if($request->date){
                    $query->where('date',$request->date);
                }
                return $query;
            })->get();
           
            $event = Event::findOrFail($eventId);
            
            /*$teams = $event->teams()->get()->through(function ($team) use($user, $teamService){
                $team = $teamService->formatTeam($team, $user);
                return $team;
            });*/
           
            $event->user = compact('participation','points');
            
            $membership = $user->memberships()->where(['event_id' => $event->id])->first();
            
            $team = $membership?$membership->team:null;
            if($team) {
                $team->is_team_owner = $team->owner_id == $user->id;   
            }
        
        
            $event->has_team = !is_null($membership);
            $event->preferred_team_id = $membership?$team->id:null;
            $event->preferred_team = $membership?$team:null;
            $event->is_expired = !Carbon::parse($event->end_date)->gt(Carbon::now());
             $event->statistics = $eventService->userMileStatics($event, $user);
             
            $questRegistrations = $user->questRegistrations()->whereHas('activity', function($query) use($event){
                return $query->where('event_id', $event->id);
            })->get();
            
            $questStatics = [];
            
             $questStatics['total_quests'] = $questRegistrations->count();
                
                $questCompletedCount = 0;
                $questPendingCount = 0;
            
            if($questRegistrations->count()){
                
               
            
                foreach($questRegistrations as $questRegistration){
                     $milestone = $questRegistration->activity->milestones()->select(['id'])->where('total_points','<=',1000)->latest('total_points')->first();
                 
                 $hasCount = $questRegistration->milestoneStatuses()->where(['milestone_id' => $milestone?$milestone->id:0])->count();
                 
                 if($hasCount){
                     $questCompletedCount += 1;
                 } else {
                     $questPendingCount +=1;
                 }
                
                }
                
                
            }
            
            $questStatics['completed_quests'] = $questCompletedCount;
            $questStatics['pending_quests'] = $questPendingCount;
           
            $event->quest_statistics = $questStatics;
             
             Cache::put($cacheName, $event, now()->addHours(2));
            return $this->sendResponse($event, 'Response');
       } catch (Exception $e){ 
            return $this->sendError('Invalid Event ID.', ['error'=> "Invalid event ID"]);
       }
    }
    
    public function eventMissingYears(Request $request, EventService $eventService): JsonResponse
    {
         $request->validate([
            "event_id" => [ 
                'required',
                 Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        $user = $request->user();

        $participation = $user->participations()->where('event_id',$request->event_id)->first();
                
        if(is_null($participation)){
            return $this->sendError('ERROR', ['error'=>'User is not participating in this event']);
        }
        
        $userPointYears = $user->points()->where(['event_id' => $request->event_id])
        ->selectRaw("extract(year from date) as y")->groupBy('y')
        ->pluck('y')
        ->filter(function($item){
            return ($item != Carbon::now()->format('Y'));
        })
        ->unique()->values()->all();
        
        
        $years = range(2016, Carbon::now()->subYear(1)->format('Y'));
        
        $years = collect($years)->filter(function($year) use($userPointYears) {
            return !in_array($year, $userPointYears);
        })->unique()->values()->all();
        
        return $this->sendResponse($years, 'Milssing Mile Years');
    }
    
    public function importEventMiles(Request $request, EventService $eventService): JsonResponse
    {
        $request->validate([
            "manual_entry.*.year" => 'required|distinct|digits:4',
            "manual_entry.*.miles" => 'required|numeric',
            "event_id" => [ 
                'required',
                 Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        $user = $request->user();

        $participation = $user->participations()->where('event_id',$request->event_id)->first();
                
        if(is_null($participation)){
            return $this->sendError('ERROR', ['error'=>'User is not participating in this event']);
        }
        
        $event = $participation->event;
        
        foreach($request->manual_entry as $manualEntry) {
            $eventService->importManual($event, $manualEntry, $user);
          
        }
        
        return $this->sendResponse([], 'Manual Entry uploaded');
    }
    
    public function getModalities()
    {
        try {
            $cacheKey = "event_modalities";
            
            $modalities = Cache::remember($cacheKey, now()->addDay(), function () {
                return Modality::select('id', 'name')->get();
            });

            return $this->sendResponse([
                'modalities' => $modalities
            ], 'Event modalities retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Error retrieving modalities', ['error' => $e->getMessage()]);
        }
    }
    
    public function getEventModalities(Request $request)
    {
        try {
            $request->validate([
                "event_id" => [ 
                    'required',
                    Rule::exists((new Event)->getTable(),'id'),
                ]
            ]);
                
            $eventModalities = $this->decodeModalities(Event::where('id', $request->event_id)->value('supported_modalities'));
            
            return $this->sendResponse([
                'event_modalities' => $eventModalities
            ], 'Event modalities retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError('Error retrieving event modalities', ['error' => $e->getMessage()]);
        }
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
            'other' => 32
        ];
    
        foreach ($modalities as $key => $value) {
            if (($sum & $value) !== 0) {
                $decoded[] = $key;
            }
        }
    
        return $decoded;
    }
    
    private function getAllPathDistances()
    {
        $cacheKey = "amerithon_path_distances_all";

        // Try to get all data from cache first
        $allData = Cache::get($cacheKey);

        // If cache doesn't exist, fetch data in chunks and store in cache
        if (!$allData) {
            $allData = collect();
            $perPage = 1000; // Adjust chunk size as needed

            AmerithonPathDistance::orderBy('id')->chunk($perPage, function (Collection $chunk) use (&$allData) {
                $allData = $allData->concat($chunk->map(function ($item) {
                   return [
                       'id' => $item->id,
                       'distance' => $item->distance,
                       'coordinates' => $item->coordinates
                   ];
                }));
            });

            // Store in cache for 1 year
            Cache::put($cacheKey, $allData, now()->addYear());
        }

        return $allData;
    }
    
    public function getAmerithonPathDistances(Request $request): JsonResponse
    {
        try {
            $request->validate([
                "distance" => [ 
                    'required'
                ]
            ]);
            
            // Find the nearest record
            $nearest = AmerithonPathDistance::select('id', 'distance', 'coordinates')
                ->orderByRaw('ABS(distance - ?)', [$request->distance])
                ->first();

            if (!$nearest) {
                return $this->sendError('No path distances found', ['error' => 'No records available']);
            }
            
            // Get previous record
            $previous = AmerithonPathDistance::select('id', 'distance', 'coordinates')
                ->where('distance', '<', $nearest->distance)
                ->orderBy('distance', 'desc')
                ->first();

            // Get next record
            $next = AmerithonPathDistance::select('id', 'distance', 'coordinates')
                ->where('distance', '>', $nearest->distance)
                ->orderBy('distance', 'asc')
                ->first();

            return $this->sendResponse([
                'previous' => $previous ? [
                    'distance' => $previous->distance,
                    'coordinates' => $previous->coordinates
                ] : null,
                'nearest' => [
                    'distance' => $nearest->distance,
                    'coordinates' => $nearest->coordinates
                ],
                'next' => $next ? [
                    'distance' => $next->distance,
                    'coordinates' => $next->coordinates
                ] : null
            ], 'Path distances retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving path distances', ['error' => $e->getMessage()]);
        }
    }
}
