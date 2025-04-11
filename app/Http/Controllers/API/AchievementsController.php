<?php

namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\{
    UserService,
    TeamService
};
use Illuminate\Validation\Rule;
use App\Models\{
    Event,
    DataSource,
    Team
    };

class AchievementsController extends BaseController
{
     public function index(Request $request, UserService $userService, TeamService $teamService): JsonResponse
    {
   
        $request->validate([
            "event_id" =>  [
                'required',
                Rule::exists(Event::class,'id'),
            ],
            "action" => "required|in:user,team"
        ]);
        
        $isTeamAction = $request->action == 'team';

        $startOfMonth =  Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth =  Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
        
        $today = Carbon::now()->format('Y-m-d');
        
           $user = $request->user();
        
        $cacheName = "user_achievement_{$user->id}_{$request->action}_{$request->event_id}_{$startOfMonth}_{$endOfMonth}_{$startOfWeek}_{$endOfWeek}_{$request->team_id}";
       
       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
           //return $this->sendResponse($item, 'Response'); 
       }
        
     
        
        $event = Event::find($request->event_id);
        
        if($isTeamAction) {
            $team = Team::where(function($query) use ($user){
               return $query->where('owner_id',$user->id)
               ->orWhereHas('memberships', function($query) use($user) {
                   return $query->where('user_id', $user->id);
               });
           })
           ->where('event_id', $request->event_id)
           ->where('id', $request->team_id)
           ->first();
           
           //$team = Team::find($request->team_id);
           
           if(is_null($team)){
                return $this->sendError('Event or Team not found.', ['error'=>'User is not associated with this event or team']);
           }
           
           list($achievementData,$totalPoints,$yearwisePoints) = $teamService->achievements($event, [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek], $team);

        } else {
        
            list($achievementData,$totalPoints,$yearwisePoints) = $userService->achievements($event, [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek], $user);
        
        }
        
        $data = [
       
            'achievement' => $achievementData,
            'miles' => [
                'total' => $totalPoints,
                'chart' => $yearwisePoints
            ],
            'event' => $event
        ];
        
           Cache::put($cacheName, $data, now()->addHours(2));
        
         return $this->sendResponse($data, 
        'Response');
        
        
    }
}
