<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\StravaService;

class TrackerLoginsController extends Controller
{
    
    public function redirectToAuthUrl(StravaService $stravaService){
        $authUrl = $stravaService->authUrl('app');
        
        return redirect($authUrl);
    }
    
    public function index(Request $request,StravaService $stravaService){
        
        if($request->get('action') == 'logout') {
         $request->session()->invalidate();   
         return redirect()->route('tracker.login');
        }
        
        $authUrl = $stravaService->authUrl();
        $user = $request->session()->has('tracker_user');
        
        if($user) {
            return redirect()->route('tracker.user.activities');
        }
        return view('tracker-login',compact('authUrl','user'));
    }
    
    public function stravaCallback(Request $request, StravaService $stravaService){
        
        if($request->get('debug') == true) {
            
            /*
              0 => "run"
  1 => "walk"
  2 => "bike"
  3 => "swim"
  4 => "other"
  5 => "daily_steps"
  */
            $data = $stravaService->setAccessToken("f2de4c3e7f96694c210a042a3417a7572d3ba581")->activities(request()->get('date'));
            
              $activities = collect($data);
            
            return $activities;
        }
        
        
        $response = $stravaService->authorize($request->get('code'));
        //dd($response);
        if($request->get('state') == 'app') {
            if($response == false) {
                //error=access_denied
                /*
                {#467 ▼ // app/Http/Controllers/TrackerLoginsController.php:44
  +"token_type": "Bearer"
  +"expires_at": 1738194171
  +"expires_in": 21577
  +"refresh_token": "d07d83e530a0b283bf96489ade8a9bee977c8c23"
  +"access_token": "a1b67d68c3fd5712769c802ab4a54a7ea977b7f8"
  +"athlete": {#471 ▼
    +"id": 16005041
    +"username": null
    +"resource_state": 2
    +"firstname": "Scott"
    +"lastname": "Putnam"
    +"bio": null
    +"city": null
    +"state": null
    +"country": null
    +"sex": "M"
    +"premium": false
    +"summit": false
    +"created_at": "2016-06-26T19:46:12Z"
    +"updated_at": "2025-01-29T17:24:24Z"
    +"badge_type_id": 0
    +"weight": 91.6257
    +"profile_medium": "https://graph.facebook.com/10210176406546183/picture?height=256&width=256"
    +"profile": "https://graph.facebook.com/10210176406546183/picture?height=256&width=256"
    +"friend": null
    +"follower": null
  }
}
                */
               // dd($response);
                return response()->json(['message' => 'Unabled to complete your request'],403);
            }
            
            
            return redirect(sprintf("rte://settings/%s/%s/%s/%s/%s/1", $response->access_token,$response->refresh_token,$response->expires_in,$response->athlete->id,'strava'));
            
            return response()->json(['message' => 'Authentication completed']);
        }
        
        if($response == false) {
            $request->session()->flash('status', ['type' => 'danger','message' => 'Unabled to complete your request']);
            
            return redirect()->route('tracker.login');
        }
        
        $request->session()->put('tracker_user', $response);
         $request->session()->flash('status', ['type' => 'success','message' => 'Welcome back, you can track your activities now']);
        
        return redirect()->route('tracker.user.activities');
    }
    
    public function userActivities(Request $request,StravaService $stravaService) {
        
         $user = $request->session()->get('tracker_user');
        
        if(!$user) {
            return redirect()->route('tracker.login');
        }
      
        $date = Carbon::parse($request->get('date',Carbon::now()));

        $data = $stravaService->setAccessToken($user->access_token)->activities(request()->get('date'));
 
        $activities = collect($data);
       
        return view('tracker-login',compact('user','activities','date'));
        
    }
}
