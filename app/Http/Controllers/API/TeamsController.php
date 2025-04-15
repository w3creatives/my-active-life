<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Closure;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\{
    EventParticipation,
    Team,
    TeamPointMonthly,
    TeamPointTotal,
    User,
    Event
};
use App\Services\{
    UserService,
    TeamService,
    MailService
};

use Illuminate\Support\Facades\Log;

class TeamsController extends BaseController
{
    public function all(Request $request): JsonResponse
    {
   
        $allType = $request->list_type;
        
        /*$request->validate([
            //'event_id'=>'required|numeric'
            'event_id' => [
                Rule::requiredIf(!$ownTeamOnly),
                Rule::exists(Event::class,'id'),
            ],
        ]);*/
        
       $user = $request->user();
      
       $pageLimit = $request->page_limit??100;
       
       $searchTerm = $request->term;
       
       $eventId = $request->event_id;
       
       $pageNum = $request->page??1;
       
         $cacheName = "team_{$user->id}_{$allType}_{$request->event_id}_{$searchTerm}_{$pageLimit}_$pageNum";
       
       if(Cache::has($cacheName)){
          // $item = Cache::get($cacheName);
           //return $this->sendResponse($item, 'Response'); 
       }
       
       $teams = Team::where(function($query) use ($user, $allType, $searchTerm){
      
                switch($allType){
                    case 'own':
                        $query->where('owner_id',$user->id);
                        break;
                    case 'other':
                        $query->where('owner_id','!=',$user->id);
                        break;
                    case 'joined':
                        $query->whereHas('memberships', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                        break;
                }
                
                if($searchTerm) {
                    $query->where('name','LIKE',"{$searchTerm}%");
                }
           
               return $query;
       })
       /*
       whereHas('memberships', function($query) use($user) {
                   return $query->where('user_id', $user->id);
               })
              ->
       */
       ->where(function($query) use ($request){
           if(!$request->event_id) {
               return $query;
           }
               
           return $query->where('event_id', $request->event_id);
       })
       ->simplePaginate($pageLimit,['id','name','public_profile','settings','owner_id','event_id'])
       ->through(function ($team) use($user,$eventId){
            $team->is_team_owner = $team->owner_id == $user->id;
            
            $membershipStatus = null;
            if($team->requests()->where(["prospective_member_id" => $user->id,"event_id" => $eventId])->count()){
                $membershipStatus = "RequestedJoin";
            } else if($team->memberships()->where(["user_id" => $user->id,"event_id" => $eventId])->count()){
                $membershipStatus = "Joined";
            } else  if($team->invites()->where(["prospective_member_id" => $user->id,"event_id" => $eventId])->count()){
                $membershipStatus = "JoinInProcess";
            }
            $team->membership_status = $membershipStatus;
           unset($team->requests);
            unset($team->memberships);
            unset($team->invites);
            unset($team->owner_id);
            return $team;
        });
        Cache::put($cacheName, $teams, now()->addHours(2));
       return $this->sendResponse($teams, 'Response');
    }
    
    public function findOne(Request $request, TeamService $teamService, $id): JsonResponse
    {
         $request->validate([
            'event_id' => [
                'required',
                Rule::exists((new EventParticipation)->getTable()),
            ]
        ]);
        
         $cacheName = "team_{$id}";
       
       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
          // return $this->sendResponse($item, 'Response'); 
       }
        
        $team = $teamService->find($id);
        
        if(is_null($team)){
            return $this->sendError('Event or Team not found.', ['error'=>'User is not associated with this event or team']);
       }
        
        $team = $teamService->formatTeam($team, $request->user());
         Cache::put($cacheName, $team, now()->addHours(2));
        return $this->sendResponse($team, 'Response');
    }
    
    public function points(Request $request): JsonResponse
    {
   
        $request->validate([
            'event_id'=>'required|numeric'
        ]);
        
       $user = $request->user();
       
       $pageNum = $request->page??1;
       
        $cacheName = "team_{$user->id}_{$request->event_id}_{$pageNum}";
       
       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
           //return $this->sendResponse($item, 'Response'); 
       }
   
       $teams = Team::where(function($query) use ($user){
           return $query->where('owner_id',$user->id)
           ->orWhereHas('memberships', function($query) use($user) {
               return $query->where('user_id', $user->id);
           });
       })
       ->where('event_id', $request->event_id)
       ->simplePaginate(100,['id','name','public_profile','settings','owner_id'])
       ->through(function ($team) use($user){
            $team->is_team_owner = $team->owner_id == $user->id;
            unset($team->owner_id);
            return $team;
        });
       Cache::put($cacheName, $teams, now()->addHours(2));
       return $this->sendResponse($teams, 'Response');
    }
    
    public function achievements(Request $request): JsonResponse
    {
   
        $request->validate([
            'event_id'=>'required|numeric',
            'team_id' => [
                'required',
                'numeric',
            ]
        ]);
        
       $user = $request->user();
       
       $year = $request->year?$request->year:Carbon::now()->format('Y');
        
        $cacheName = "achievement_{$user->id}_{$request->event_id}_{$request->team_id}_{$year}";
       
       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
          // return $this->sendResponse($item, 'Response'); 
       }
   
       
       $team = Team::where(function($query) use ($user){
           return $query->where('owner_id',$user->id)
           ->orWhereHas('memberships', function($query) use($user) {
               return $query->where('user_id', $user->id);
           });
       })
       ->where('event_id', $request->event_id)
       ->where('id', $request->team_id)
       ->first();
       
       if(is_null($team)){
            return $this->sendError('Event or Team not found.', ['error'=>'User is not associated with this event or team']);
       }
       
        $achievements = $team->achievements()->select(['accomplishment','date','achievement'])
        ->hasEvent($request->event_id)
        ->latest('accomplishment')
        ->get()
        ->groupBy('achievement');
        
         $date = Carbon::createFromDate($year, 01, 01);

        $startOfYear = $date->copy()->startOfYear()->format('Y-m-d');
        $endOfYear   = $date->copy()->endOfYear()->format('Y-m-d');
        
 
        $point = $team->monthlyPoints()->selectRaw('SUM(amount) as totalPoint')
        ->hasEvent($request->event_id)
        ->whereBetween('date',[$startOfYear, $endOfYear])
        ->first()->totalpoint;
        
        $users = $team->memberships()->where('event_id', $request->event_id)->with('user')->get();
        
        $users = $users->map(function($item) use($request, $startOfYear, $endOfYear, $year, $team){
            $user = $item->user->only(['id','display_name']);
            
            // Add is_admin flag
            $user['is_admin'] = $team->owner_id === $item->user->id;
            
            $point = $item->user->monthlyPoints()->selectRaw('SUM(amount) as totalPoint')
            ->hasEvent($request->event_id)
            ->whereBetween('date',[$startOfYear, $endOfYear])
            ->first()->totalpoint;
        
            $yearlyPoints = compact('point','year');
            
           $user['achievements'] =  $item->user->achievements()->select(['accomplishment','date','achievement'])->hasEvent($request->event_id)->latest('accomplishment')->get()->groupBy('achievement');
           $user['yearlyPoints'] = $yearlyPoints;
           $user['recentActivity'] = $item->user->points()->where('event_id', $request->event_id)->latest()->select(['id','amount','date','modality'])->first();
           
           return $user;
        });
        
        $yearlyPoints = compact('point','year');
        
         Cache::put($cacheName, compact('achievements','yearlyPoints','users'), now()->addHours(2));
       
       return $this->sendResponse(compact('achievements','yearlyPoints','users'),'Response');
       
       return $this->sendResponse($teams, 'Response');
    }
    
    public function inviteMembership(Request $request, MailService $mailService): JsonResponse 
    {
        
        $request->validate([
            'emails.*' => [
                    'required',
                    'email',
                    Rule::exists((new User)->getTable(),'email'),
                    function (string $attribute, mixed $value, Closure $fail) use($request){
                        
                        $member = User::where('email',$value)->first();
                        
                        if(!$member){
                             return false;
                        }
                        
                        $teamId = $request->team_id;
                        
                        $isExistingMember = Team::where(function($query) use ($request,$member){
                           return $query->where('owner_id',$member->id)
                           ->orWhereHas('memberships', function($query) use($request,$member) {
                               return $query->where('user_id', $member->id)->where('event_id', $request->event_id);
                           });
                       })
                       ->where('event_id', $request->event_id)->where('id',$teamId)->count();
                         
                       if($isExistingMember) {
                            //$errors[] = "Unfortunately, user {$email} already participates in the same team.";
                            //continue;
                            $fail("Unfortunately, user {$value} already participates in the same team.");
                            return false;
                       }
                       
                        $isExistingMemberInOtherTeam = Team::where(function($query) use ($request, $member){
                               return $query->where('owner_id',$member->id)
                               ->orWhereHas('memberships', function($query) use($request,$member) {
                                   return $query->where('user_id', $member->id)->where('event_id', $request->event_id);
                               });
                           })
                           ->where('event_id', $request->event_id)->where('id','!=',$teamId)->count();
                           
                        if($isExistingMemberInOtherTeam) {
                                $fail("Unfortunately, user {$value} already participates in another team.");
                                return false;
                           }
                           
                         
                        return true;
                    }
            ],
            'event_id' => [
                'required',
                Rule::exists((new EventParticipation)->getTable()),
            ],
            'team_id' => [
                'required',
                'numeric',
                Rule::exists(Team::class,'id'),
            ],
        ],
        [
            "emails.*.exists" => "Ah Shucks! This person (:input) is either not yet registered for the this Challenge or this in the wrong email. Check with them and try again!"
        ]);
        
        $errors = [];
        $mailList = [];
        
        foreach($request->emails as $email) {
            $member = User::where('email',$email)->first();
        
            $teamId = $request->team_id;
            
            
            /*
            $isExistingMember = Team::where(function($query) use ($request,$member){
               return $query->where('owner_id',$member->id)
               ->orWhereHas('memberships', function($query) use($request,$member) {
                   return $query->where('user_id', $member->id)->where('event_id', $request->event_id);
               });
           })
           ->where('event_id', $request->event_id)->where('id',$teamId)->first();
           
             
           if(!is_null($isExistingMember)) {
                $errors[] = "Unfortunately, user {$email} already participates in the same team.";
                continue;
           }
           
           $isExistingMemberInOtherTeam = Team::where(function($query) use ($request, $member){
               return $query->where('owner_id',$member->id)
               ->orWhereHas('memberships', function($query) use($request,$member) {
                   return $query->where('user_id', $member->id)->where('event_id', $request->event_id);
               });
           })
           ->where('event_id', $request->event_id)->where('id','!=',$teamId)->first();
           
            if(!is_null($isExistingMemberInOtherTeam)) {
                $errors[] = "Unfortunately, user {$email} already participates in another team.";
                continue;
           }
           */
           
           $member->invites()->where(['status' => 'invite_to_join_issued','team_id' => $teamId, 'event_id' => $request->event_id])->delete();
    
           
           $invite = $member->invites()->create(['status' => 'invite_to_join_issued','team_id' => $teamId, 'event_id' => $request->event_id]);
           
            $mailService->sendTeamInviteEmail($email, $teamId);
        }

        
        /*if(count($errors) == count($request->emails)){
             return $this->sendError('Error', ['error'=> $errors,'invitat']);
        }*/
        
        $error = collect($errors)->first();
        
        if(count($errors)) {
            return $this->sendError('Team membership invitation could not be sent to some users', ['error'=> $errors,'d' => $error]);//$this->sendError(['error' => $errors], 'Team membership invitation could not be sent to some users');
        }
        /*
        if(count($errors)) {
            return $this->sendError('Team membership invitation could not be sent to some users', ['error'=> $errors]);//$this->sendError(['error' => $errors], 'Team membership invitation could not be sent to some users');
        }*/
        
        return $this->sendResponse([], 'Team membership invitation has been sent');
       
        /*422
        "message": "The team id field is required.",
    "errors": {
        "team_id": [
            "The team id field is required."
        ]*/
        //participations
        
    }
    
    public function joinTeam(Request $request): JsonResponse 
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        
        $user = $request->user();
        
        $team = Team::where(['id' => $request->team_id,'event_id' => $request->event_id])->first();
        
        if(is_null($team)) {
            return $this->sendError('Team does not belong to given event ID', ['error'=>'Team does not belong to given event ID']);
        }
        
        $hasTeamMember = $team->memberships()->where(['user_id' => $user->id,'event_id' => $request->event_id])->count();
        
        if($hasTeamMember) {
            return $this->sendError('Your are already a member of team', ['error'=>'Your are already a member of team']);
        }
        
        $hasInvite = $user->invites()->where(['team_id' => $request->team_id,'event_id' => $request->event_id])->count();
        
        if($hasInvite) {
            return $this->sendError('Your invite to join team is already exist', ['error'=>'Your invite to join team is already exist']);
        }
        
        if($team->public_profile == true) {
            $team->memberships()->create(['event_id' => $request->event_id,'user_id' => $user->id]);
            
            return $this->sendResponse([], 'You have joined the team');
        }
        
        $hasRequest = $user->requests()->where(['team_id' => $request->team_id,'event_id' => $request->event_id])->count();
    
        if($hasRequest) {
            return $this->sendError('Your request to join team is already exist', ['error'=>'Your request to join team is already exist']);
        }
        
        $user->requests()->create(['team_id' => $request->team_id,'event_id' => $request->event_id,'status' => 'request_to_join_issued']);
        
        return $this->sendResponse([], 'You have requested to join the team');

    }
     public function cancelJoinTeam(Request $request): JsonResponse 
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        
        $user = $request->user();
        
        $team = Team::where(['id' => $request->team_id,'event_id' => $request->event_id])->first();
        
        if(is_null($team)) {
            return $this->sendError('Team does not belong to given event ID', ['error'=>'Team does not belong to given event ID']);
        }
        
        $hasRequest = $user->requests()->where(['team_id' => $request->team_id,'event_id' => $request->event_id])->count();
        
        if(!$hasRequest) {
            return $this->sendError('No record found', ['error'=>'No record found']);
        }
        
        
        $user->requests()->where(['team_id' => $request->team_id,'event_id' => $request->event_id])->delete();
        
        return $this->sendResponse([], 'Team join request cancelled');
        
    }
      public function leaveTeam(Request $request): JsonResponse 
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ]
        ]);
        
        $user = $request->user();
        
        $team = Team::find($request->team_id);
        
        $hasTeamMember = $team->memberships()->where(['user_id' => $user->id])->count();
        
        if(!$hasTeamMember) {
            return $this->sendError('Team not found', ['error'=>'Team not found']);
        }
        
        $team->memberships()->where(['user_id' => $user->id])->delete();
        
        if($team->memberships()->count()){
            if($team->owner_id == $user->id){
                $member = $team->memberships()->first();
                $team->fill(['owner_id' => $member->user_id]);
                return $this->sendResponse([], 'Team admin has been reassigned');
            }
        } else {
            $message = sprintf('You are the last one to leave team %s. Successfully deleted team %s.',$team->name, $team->name);
            $this->deleteTeamForeignData($request->team_id);
            $team->delete();
            return $this->sendResponse([], $message);

        }

        return $this->sendResponse([], 'You have successfully left your team.');
    }
    
    public function store(Request $request, $id = null): JsonResponse
    {
        
        if($id) {
            $request->validate([
                'name' => 'sometimes|required|string',
                'public_profile' => 'sometimes|required|boolean',
            ]);
        } else {
            $request->validate([
                'event_id' => [
                    'required',
                    Rule::exists(Event::class,'id'),
                ],
                'name' => ['required','string'],
                'public_profile' => 'required|boolean',
            ]);
        }
        
        $user = $request->user();
        
        $team = $user->teams()->find($id);
        
        if($id && !$team){
            return $this->sendError('Team not found.', ['error'=>'Team not found']);
        }
        
        if($team){
            $teamData = $request->only(['name','public_profile']);
     
            //$teamData['settings'] = $teamData['settings']?$teamData['settings']:'{}';
            try{
                $team->fill($teamData)->save();
                return $this->sendResponse([], 'Team has been updated');
            } catch(Exception $e){
                return $this->sendError('Duplicate Entry.', ['error'=>'Team name already exists']);
            }
            
        }
        
        $team = Team::where(function($query) use ($user){
           return $query->where('owner_id',$user->id)
           ->orWhereHas('memberships', function($query) use($user) {
               return $query->where('user_id', $user->id);
           });
       })
       ->where('event_id', $request->event_id)
       ->first();
       
       if(!is_null($team)){
            return $this->sendError('Team has already been created for given event.', ['error'=>'Team has already been created for given event']);
       }
       
       $teamData = $request->only(['event_id','name','public_profile','settings']);
       
       $teamData['settings'] = $teamData['settings']?$teamData['settings']:'{}';
       
        $teamData['owner_id'] = $user->id;
        $team = Team::create($teamData);
        
        $team->memberships()->create(['user_id' => $user->id,'event_id' => $team->event_id]);

       $data = $team->only(['id','name','public_profile','settings','owner_id']);
       $data['is_team_owner'] = true;
       
       return $this->sendResponse($data, 'Team has been created');
    }
    
    public function removeMember(Request $request): JsonResponse 
    {
        
        $request->validate([
            'user_id' => [
                'required',
                Rule::exists(User::class,'id'),
            ],
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        
           
        $user = $request->user();
        
        $team = $user->teams()->find($request->team_id);
        
        if(is_null($team)) {
            return $this->sendError('Team not found', ['error'=>'Team not found']);
        }
        
        $hasMember = $team->memberships()->where(['event_id' => $request->event_id,'user_id' => $request->user_id])->count();
        
        if(!$hasMember) {
            return $this->sendError('NOT FOUND', ['error'=>'Member not found']);
        }
        
        $team->memberships()->where(['event_id' => $request->event_id,'user_id' => $request->user_id])->delete();
           
        return $this->sendResponse([], 'Member removed from the team');
    }
    
    public function joinTeamRequest(Request $request,$type): JsonResponse 
    {
        
        $request->validate([
            'user_id' => [
                'required',
                Rule::exists(User::class,'id'),
            ],
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        
        if(!in_array($type,['accept','decline'])) {
            return $this->sendError('Invalid Request', ['error'=>'Invalid Request']);
        }
        
        $user = $request->user();
        
        $team = $user->teams()->find($request->team_id);
        
        if(is_null($team)) {
            return $this->sendError('Team not found', ['error'=>'Team not found']);
        }
        
        $memberRequestUser = User::find($request->user_id);
        
        $membershipRequest = $memberRequestUser->requests()->where(['team_id' => $request->team_id,'event_id' => $request->event_id])->first();
        
        if(is_null($membershipRequest)) {
            return $this->sendError('NOT FOUND', ['error'=>'Request not found']);
        }
        
        if($type == 'accept') {
            
            $hasMembership = $team->memberships()->where(['event_id' => $request->event_id,'user_id' => $memberRequestUser->id])->count();
            
            if($hasMembership) {
                return $this->sendError('NOT FOUND', ['error'=>'Invalid Request']);
            }
            
            $membershipRequest->delete();
            $team->memberships()->create(['event_id' => $request->event_id,'user_id' => $memberRequestUser->id]);
            return $this->sendResponse([], 'Request accepted');
        }
        
        $membershipRequest->delete();
        
        return $this->sendResponse([], 'Request declined');
    }
    
    public function membershipRequests(Request $request): JsonResponse 
    {
         $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        
        $pageNum = $request->page??1;
        
         $cacheName = "team_membership_request_{$request->team_id}_{$request->event_id}_{$pageNum}";
       
       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
           //return $this->sendResponse($item, 'Response'); 
       }
        
        $team = Team::find($request->team_id);
        
        $memberRequests = $team->requests()->with('user',function($query){
            return $query->select(['id','first_name','last_name','display_name','email']);    
        })
        ->simplePaginate(100);
        
        /*->through(function ($member){
            $team->user;
            [
                'id' => $member->id,
                'created_at' => $member->updated_at,
                'user' => ['id' => $member->user]
            ];
            return $team;
        });;*/
        
           Cache::put($cacheName, $memberRequests, now()->addHours(2));
        
        return $this->sendResponse($memberRequests, 'Response');
    }
    
    public function membershipInvites(Request $request): JsonResponse 
    {
         $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        
        $pageNum = $request->page??1;
        
        $cacheName = "team_membership_invite_{$request->team_id}_{$request->event_id}_{$pageNum}";
       
       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
           //return $this->sendResponse($item, 'Response'); 
       }
        
        $team = Team::find($request->team_id);
        
        $memberRequests = $team->invites()->with('user',function($query){
            return $query->select(['id','first_name','last_name','display_name','email']);    
        })
        ->simplePaginate(100);
        
         Cache::put($cacheName, $memberRequests, now()->addHours(2));
        
        return $this->sendResponse($memberRequests, 'Response');
    }
    
    private function deleteTeamForeignData($teamId){
        $tables = DB::select("select table_name from information_schema.columns where column_name = 'team_id'");
      
        foreach($tables as $table) {
            DB::table($table->table_name)->where('team_id', $teamId)->delete();
        }
    }
    
    public function dissolveTeam(Request $request): JsonResponse 
    {
          $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ]
        ]);
        
        $user = $request->user();
        
        $team = $user->teams()->find($request->team_id);
        
        if(is_null($team)) {
            return $this->sendError('Team not found', ['error'=>'Team not found']);
        }
        
        $this->deleteTeamForeignData($request->team_id);
        
        $team->delete();
        
         return $this->sendResponse([], 'Team has been dissolved');
    }
    
    public function transferTeamAdminRole(Request $request): JsonResponse 
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'member_id' => [
                'required',
                Rule::exists(User::class,'id'),
            ]
        ]);
        
        $user = $request->user();
        
        $team = $user->teams()->find($request->team_id);
        
        if(is_null($team)) {
            return $this->sendError('Team not found', ['error'=>'Team not found']);
        }
        
        $member = $team->memberships()->where(['user_id' => $request->member_id])->first();
        
        if(is_null($member)) {
            return $this->sendError('User is not a member of given team', ['error'=>'User is not a member of given team']);
        }
        
         $team->fill(['owner_id' => $member->user_id])->save();
               
        return $this->sendResponse([], 'Team admin role has been transferred');
    }
    
    public function searchInvitationUsers(Request $request): JsonResponse 
    {
        $request->validate([
            /*'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],*/
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        
        $email = $request->email;
        
        $users = User::select(['id','first_name','last_name','display_name','email'])
        ->where(function($query) use($email){
            
            if($email) {
                return $query->where('email','LIKE','%'.$email.'%');
            }
            
            return $query;
        })
        ->whereDoesntHave('memberships', function($query) use($request){
            return $query->where(['event_id' => $request->event_id]);
        })->simplePaginate(100);

        return $this->sendResponse($users, 'Response');
    }
    
    
    public function teamToFollowList(Request $request): JsonResponse 
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
        ]);
        

       $user = $request->user();
      
       $pageLimit = $request->page_limit??100;
       
       $searchTerm = $request->term;
       
       $eventId = $request->event_id;
       
       $pageNum = $request->page??1;
       
      
       $teams = Team::where(function($query) use ($user, $searchTerm){
  
            $query->where('owner_id','!=',$user->id);
            
            if($searchTerm) {
                $query->where('name','LIKE',"{$searchTerm}%");
            }
       
           return $query;
       })
   
       ->where(function($query) use ($request){

           return $query->where('event_id', $request->event_id);
       })
       ->simplePaginate($pageLimit,['id','name','public_profile','settings','owner_id','event_id'])
       ->through(function ($team) use($user,$eventId){
            $team->is_team_owner = $team->owner_id == $user->id;
            
            $followStatus = $team->public_profile?"follow":"request_follow";
        
            if($followRequest = $team->followerRequests()->where(["prospective_follower_id" => $user->id,"event_id" => $eventId])->first()){
                
                if(in_array($followRequest->status, ['request_to_follow_issued', 'request_to_follow_approved'])){
                    $followStatus = $followRequest->status;
                }
            }
            $team->follow_status = $followStatus;
            $team->follow_status_text = ['follow'=>"Follow",'request_follow' => 'Request','request_to_follow_issued'=>'Requested','request_to_follow_approved'=>'Following'][$followStatus];
          
            unset($team->owner_id);
            return $team;
        });
       return $this->sendResponse($teams, 'Response');
    
    }
    
    public function teamFollowRequestAction(Request $request, $type): JsonResponse 
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ],
            'event_id' => [
                'required',
                Rule::exists(Event::class,'id'),
            ],
             'user_id' => [
                'required',
                Rule::exists(User::class,'id'),
            ],
        ]);
        
        $user = $request->user();
        
        $team = $user->teams()->where(['id' => $request->team_id, 'event_id' => $request->event_id])->first();
        
        if(is_null($team)) {
            return $this->sendError('Team not found', ['error'=>'Team not found']);
        }
        
        $follower = User::find($request->user_id);
        
        $followRequest = $team->followerRequests()->where(['prospective_follower_id' => $follower->id,'event_id' => $request->event_id])->first();
        
        if(is_null($followRequest)) {
            return $this->sendError('ERROR', ['error'=>'Follow request does not exist']);
        }
        
        if($type == 'accept') {
            $followRequest->fill(['status' => 'request_to_follow_approved'])->save();
            $team->followers()->create(['follower_id' => $follower->id, 'event_id' => $request->event_id]);
            return $this->sendResponse([], 'Follow request approved');
        }
        
        $followRequest->fill(['status' => 'request_to_follow_ignored'])->save();
            
        return $this->sendResponse([], 'Follow request declined');
    }
    
    public function teamFollowers(Request $request): JsonResponse 
    {
        $request->validate([
            'team_id' => [
                'required',
                Rule::exists(Team::class,'id'),
            ]
        ]);
        
        $user = $request->user();
        
          $pageNum = $request->page??1;
        
        $cacheName = "team_follower_{$user->id}_{$request->team_id}_{$pageNum}";
       
       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
          // return $this->sendResponse($item, 'Response'); 
       }
        
        $team = $user->teams()->where(['id' => $request->team_id])->first();
        
        if(is_null($team)) {
            return $this->sendError('Team not found', ['error'=>'Team not found']);
        }
        
        $followers = $team->followers()->with('user', function($query){
            return $query->select(['id','first_name','last_name','display_name']);
        })
        ->simplePaginate(100);
        
        Cache::put($cacheName, $followers, now()->addHours(2));

        return $this->sendResponse($followers, 'Response');
    }
    
    public function totalPoints(Request $request): JsonResponse
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
    
        $team = $this->getTeamForUser($request);
    
        // Retrieve total points for the specified team and event using TeamPointTotal
        $totalPoints = TeamPointTotal::where('team_id', $team->id)
            ->where('event_id', $request->event_id)
            ->sum('amount');
    
        return $this->sendResponse(['total_points' => $totalPoints], 'Total points retrieved successfully.');
    }
    
    public function monthliesPoints(Request $request): JsonResponse
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
            'start_date' => [
                'nullable',
                'date'
            ],
            'end_date' => [
                'nullable',
                'date'
            ],
        ]);

        $team = $this->getTeamForUser($request);
        $eventId = $request->event_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        
        if (!$team || !Event::where('id', $eventId)->exists()) {
            return $this->sendError('Invalid event id or team id.', ['error' => 'The provided event id or team id is not valid.']);
        }

        // Build the query for monthly points
        $monthlyPoints = TeamPointMonthly::where('event_id', $eventId)
            ->where('team_id', $team->id)
            ->when($startDate, function ($query) use ($startDate) {
                return $query->where('date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->where('date', '<=', $endDate);
            })
            ->with('event:id,event_type')
            ->get();
            // Add event_type to each monthly point
        $monthlyPoints->transform(function ($point) {
            $point->event_type = $point->event->event_type ?? null; // Fetch event_type from the related event
            return $point;
        });
        
        return $this->sendResponse($monthlyPoints, 'Monthly points retrieved successfully.');
    }
    
    private function getTeamForUser(Request $request)
    {
        $user = $request->user();
        $team = Team::where('id', $request->team_id)
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('memberships', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            })
            ->first();
    
        if (is_null($team))
        {
            return 0;
        }
    
        return $team;
    }
    
}
