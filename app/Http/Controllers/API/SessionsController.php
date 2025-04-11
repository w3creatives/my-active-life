<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;
use App\Models\{
    User,
    UserPoint,
    Team
};
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionsController extends BaseController
{
    public function login(Request $request): JsonResponse
    {

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            
            $hasTeam = Team::where(function($query) use ($user){
                   return $query->where('owner_id',$user->id)
                   ->orWhereHas('memberships', function($query) use($user) {
                       return $query->where('user_id', $user->id);
                   });
               })->first();
               
            $preferredTeam = Team::where(function($query) use ($user){
                   return $query->where('owner_id',$user->id)->where('event_id',$user->preferred_event_id)
                   ->orWhereHas('memberships', function($query) use($user) {
                       return $query->where('user_id', $user->id)->where('event_id',$user->preferred_event_id);
                   });
               })->first();
            $success['id'] = $user->id;
            $success['token'] =  $user->createToken('MyApp')->accessToken; 
            $success['name'] =  $user->display_name;
            $success['first_name'] =  $user->first_name;    
            $success['last_name'] =  $user->last_name;
            $success['email'] =  $user->email;
            $success['time_zone'] = $user->time_zone;
            $success['settings'] = $user->settings;
            $success['preferred_event_id'] = $user->preferred_event_id;
            
            $success['has_team'] = !!$preferredTeam;
            $success['preferred_team_id'] = $preferredTeam?$preferredTeam->id:NULL;
            $success['preferred_team'] = $preferredTeam;
            
            return $this->sendResponse($success, 'User login successfully.');
        } 
        else{ 
            return $this->sendError('Invalid email address or password', ['error'=>'Invalid email address or password']);
        } 
    }
   
}
