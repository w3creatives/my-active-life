<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Models\{ 
    User,
    UserFollow,
    UserFollowRequest,
    Event,
    EventParticipation
};

class UserFollowsController extends BaseController
{
    public function participates(Request $request): JsonResponse
    { 
        $user = $request->user();
   
        $request->validate([
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        $pageLimit = $request->page_limit??100;
       
        $searchTerm = $request->term;
            
        $eventId = $request->event_id;
        
        $hasParticipation = $user->participations()->where('event_id',$eventId)->count();
        
        if(!$hasParticipation) {
            return $this->sendError('ERROR', ['error'=>'You are not participating in this event']);
        }
        
        $users = EventParticipation::where('event_id',$eventId)
        ->whereHas('user', function($query) use ($searchTerm){
            if($searchTerm) {
                $query->where('first_name','LIKE',"{$searchTerm}%")
                ->orWhere('last_name','LIKE',"{$searchTerm}%")
                ->orWhere('display_name','LIKE',"{$searchTerm}%");
            }
            
            return $query;
        })
        ->simplePaginate($pageLimit)
          ->through(function ($participation) use($user,$eventId){
              $member = $participation->user;
              
              $followingTextStatus = $participation->public_profile?"Request Follow":"Follow";
              $followingStatus = null;
              
              $following = $user->following()->where('event_id', $participation->event_id)->where('followed_id',$member->id)->count();
              
              if($following){
                  $followingTextStatus = "Following";
                  $followingStatus = "following";
              } else {
                  
                  if(!$participation->public_profile){
                      $userFollowingRequest = $user->followingRequests()->where(['event_id' => $participation->event_id,'followed_id' => $member->id])->first();
                      
                      if($userFollowingRequest && $userFollowingRequest->status == 'request_to_follow_issued'){
                          $followingTextStatus = "Requested follow";
                          $followingStatus = "request_to_follow_issued";
                      } else {
                          $followingTextStatus = "Request Follow";
                          $followingStatus = "request_to_follow";
                      }
                  } else {
                        $followingTextStatus = "Follow";
                        $followingStatus = "follow";
                  }
                  
              } 
              return [
                  'id' => $member->id,
                  'display_name' => trim($member->display_name),
                  'first_name' => trim($member->first_name),
                  'last_name' => trim($member->last_name),
                  'public_profile' => $participation->public_profile,
                  'following_status_text'=> $followingTextStatus,
                  'following_status' => $followingStatus,
                ];
        });
         
        return $this->sendResponse($users, 'Response');
    }
    
    public function followers(Request $request) : JsonResponse
    {
        $user = $request->user();
   
        $request->validate([
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        $pageLimit = $request->page_limit??100;
        
        $followers = $user->followers()->where('event_id', $request->event_id)->simplePaginate($pageLimit)
         ->through(function ($item) use($user){
              $follower = $item->follower;
              
              return [
                  'id' => $follower->id,
                  'display_name' => trim($follower->display_name),
                  'first_name' => trim($follower->first_name),
                  'last_name' => trim($follower->last_name),
                  'total_miles' => $follower->totalPoints()->where('event_id', $item->event_id)->sum('amount')
                ];
        });
        
        return $this->sendResponse($followers, 'Response');
    }
    
     public function followings(Request $request) : JsonResponse
    {
        $user = $request->user();
   
        $request->validate([
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        $pageLimit = $request->page_limit??100;
        
        $followers = $user->following()->where('event_id', $request->event_id)->simplePaginate($pageLimit)
         ->through(function ($item) use($user){
              $follower = $item->following;
              
              return [
                  'id' => $follower->id,
                  'display_name' => trim($follower->display_name),
                  'first_name' => trim($follower->first_name),
                  'last_name' => trim($follower->last_name),
                  'total_miles' => $follower->totalPoints()->where('event_id', $item->event_id)->sum('amount')
                ];
        });
        
        return $this->sendResponse($followers, 'Response');
    }
    
    public function undoFollowing(Request $request) : JsonResponse
    {
        $user = $request->user();
   
        $request->validate([
            "user_id" => [
                'required',
                Rule::exists((new User)->getTable(),'id'),
            ],
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        
       $following = $user->following()->where('event_id', $request->event_id)->where('followed_id',$request->user_id)->first();
        
        if(is_null($following)) {
         return $this->sendError('ERROR', ['error'=>'Invalid action']);
        }
        
        $user->followingRequests()->where(['event_id' => $participation->event_id,'followed_id' => $member->id])->delete();
        
        $following->delete();
        
          return $this->sendResponse([], 'Unfollowed successfully');
            
        }
        
    public function followingRequests(Request $request) : JsonResponse
    {
        $user = $request->user();
   
        $request->validate([
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        $memberParticipation = EventParticipation::where('event_id',$request->event_id)->where('user_id',$user->id)->first();
        
        if(!$memberParticipation) {
             return $this->sendError('ERROR', ['error'=>'Event not found']);
        }
        
        $pageLimit = $request->page_limit??100;
        
        // $query = $user->followRequests()->where('event_id', $request->event_id);
        // dd($query->toSql());
        
        $followingRequests = $user->followRequests()
        ->whereIn('status',['request_to_follow_issued','request_to_follow_ignored'])
        ->where('event_id',$request->event_id)
        ->simplePaginate($pageLimit)
         ->through(function ($item) use($user){
              $follower = $item->follower;
              
              return [
                  'id' => $follower->id,
                  'display_name' => trim($follower->display_name),
                  'first_name' => trim($follower->first_name),
                  'last_name' => trim($follower->last_name),
                  'total_miles' => $follower->totalPoints()->where('event_id', $item->event_id)->sum('amount')
                ];
        });
        
        return $this->sendResponse($followingRequests, 'Response');
    }
   
    public function requestFollowing(Request $request) : JsonResponse
    {
        $user = $request->user();
   
        $request->validate([
            "user_id" => [
                'required',
                Rule::exists((new User)->getTable(),'id'),
            ],
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        
        $memberParticipation = EventParticipation::where('event_id',$request->event_id)->where('user_id',$request->user_id)->first();
        
        if(!$memberParticipation) {
             return $this->sendError('ERROR', ['error'=>'User is not participating in this event']);
        }
        
        $userParticipation = $user->participations()->where('event_id',$request->event_id)->first();
        
        if(!$userParticipation) {
             return $this->sendError('ERROR', ['error'=>'you are not participating in this event']);
        }
        
        if($memberParticipation->public_profile){
            $user->following()->create(['event_id' => $request->event_id,'followed_id' => $request->user_id])->delete();
            $user->following()->create(['event_id' => $request->event_id,'followed_id' => $request->user_id]);
            
            return $this->sendResponse([], 'Following');
        }
        
        $followingRequest = $user->followingRequests()->where('followed_id',$request->user_id)->where('event_id',$request->event_id)->first();
        
        if($followingRequest) {
            $followingRequest->status = 'request_to_follow_issued';
            $followingRequest->save();
        }
        
        $user->followingRequests()->create(['followed_id'=>$request->user_id,'event_id'=>$request->event_id,'status' => 'request_to_follow_issued']);
        
         return $this->sendResponse([], 'Requested follow');
        
    }
    public function followRequestAction(Request $request, $action) : JsonResponse
    {
        $user = $request->user();
   
        $request->validate([
            "user_id" => [
                'required',
                Rule::exists((new User)->getTable(),'id'),
            ],
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);
        
        if(!in_array($action,['cancel','accept','decline'])) {
                 return $this->sendError('ERROR', ['error'=>'Invalid action']);
        }
        
        if($action == 'cancel') {
            $followingRequest = $user->followingRequests()->where(['event_id' => $request->event_id,'followed_id' => $request->user_id])->first();
            
            if(is_null($followingRequest)) {
                return $this->sendError('ERROR', ['error'=>'Invalid action']);
            }
            
            $followingRequest->delete();
            return $this->sendResponse([], 'Follow request cancelled');
        }
        
        
       $follower= $user->followRequests()->where('event_id', $request->event_id)->where('prospective_follower_id',$request->user_id)->first();
        
        if(is_null($follower)) {
         return $this->sendError('ERROR', ['error'=>'No record found']);
        }
        
        $isAccepted = $action == 'accept';
        
        $follower->status = $isAccepted?'request_to_follow_approved':'request_to_follow_ignored';
        $follower->save();
        
        if($isAccepted) {
            
        $hasFollower = $user->followers()->where(['event_id' => $request->event_id,'follower_id' => $request->user_id])->count();
        
        if($hasFollower) {
            return $this->sendResponse([], 'Following request is already accepted');
        }
            
        $user->followers()->updateOrCreate(['event_id' => $request->event_id,'follower_id' => $request->user_id],['event_id' => $request->event_id,'follower_id' => $request->user_id]);
         return $this->sendResponse([], 'Following request accepted');
        }
        
        return $this->sendResponse([], 'Following request declined');
       
            
        }
    
}
