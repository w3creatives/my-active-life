<?php
 
namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Exception;
use App\Services\{
    EventService,
    HubspotService
};
use App\Repositories\SopifyRepository;
use App\Models\{
    User,
    DataSourceProfile,
    DataSource,
    TrackerCron
};
use Carbon\Carbon;

class TrackersController extends Controller
{
    
    public function fitbitVerify(Request $request){
        $verificationCode = env('FITBIT_WEBHOOK_VERIFICATION_CODE');
        
        if ($request->get('verify')) {
        
            if ($request->get('verify') === $verificationCode) {
                http_response_code(204);
                exit();
            } else {
                http_response_code(404);
                exit();
            }
        }
    }
    
    public function fitBitTracker(Request $request, EventService $eventService, HubspotService $hubspotService, SopifyRepository $sopifyRepository){
     
        $notifications = collect($request->all());
        Log::stack(['single'])->debug("Fitbit Webhook",$request->all());
        foreach($notifications as $notification) {
            
            list($userId) = explode("-", $notification['subscriptionId']);
            
            $user = User::find($userId);
            
            if(is_null($user)){
                 Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - User Not Found",['userId' => $userId]);
                continue;
            }
            
            $sourceProfile = $user->profiles()->whereHas('source', function($query){
                return $query->where('short_name','fitbit');
            })->first();
            
            if(is_null($sourceProfile)){
                  Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - access token not found",[]);
                continue;
            }
            
            if(Carbon::parse($sourceProfile->token_expires_at)->lt(Carbon::now())){
                $userSourceProfile = $this->fitbitRefreshToken($sourceProfile);
                
                if($userSourceProfile) {
                    $sourceProfile = $userSourceProfile;
                }
            }
            
            
            $date = $notification['date'];
            
            try{
                
                
            $httpClient = new Client([
                'base_uri' => 'https://api.fitbit.com/1/',
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $sourceProfile->access_token),
                    'Accept' => 'application/json',
                ],
            ]);
            
            
            $activitiesResponse = $httpClient->get("user/-/activities/date/{$date}.json");

            $activities = json_decode($activitiesResponse->getBody()->getContents(), false);

            //$otherm = $totalDistance - $distances->sum('distance');
   
           // $collectionType = $notification['collectionType'];
                
           /* $response = $httpClient->get("user/-/activities/date/{$date}.json");
            //user/-/activities/distance/date/{$date}/1d.json
            $data = json_decode($response->getBody()->getContents(), false);
            } catch(Exception $e){
                $this->fitbitRefreshToken($sourceProfile);
                Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - access token not found",['e'=>$e->getMessage()]);
                continue;
            }*/
            /*$distance = collect($data->summary->distances)->filter(function($distance){
                return in_array($distance->activity, ['total','tracker','loggedActivities']);
            //})->sum('distance');
            })->sum('distance');*/
            
            $response = $httpClient->get("user/-/activities/distance/date/{$date}/1d.json");
            
            } catch(Exception $e){
                $this->fitbitRefreshToken($sourceProfile);
                Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - access token not found",['e'=>$e->getMessage()]);
                continue;
            }
            
            
            
            $data = json_decode($response->getBody()->getContents(), true)['activities-distance'];
            $distance = collect($data)->pluck('value')->first();
            
            if(!$distance) {
                $distance = 0;
            }
            
            $this->createOrUpdateUserProfilePoint($user,$distance,$date,$sourceProfile);
            
            //$distance = $distance * 0.621371;
            
            Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - Log distance",['distance' => $distance,'userId' => $userId]);
            
            
            $distances = collect($activities->summary->distances)->filter(function($item){ 
                return in_array(strtolower($item->activity),['run','walk']); 
            });
            
            $runDistances = collect($activities->summary->distances)->filter(function($item){ 
                return in_array(strtolower($item->activity),['run']); 
            })->sum('distance');
            
            $walkDistances = collect($activities->summary->distances)->filter(function($item){ 
                return in_array(strtolower($item->activity),['walk']); 
            })->sum('distance');
            
            $otherm = $distance - $distances->sum('distance');
            
            $this->createPoints($eventService, $user, $date, ($otherm * 0.621371), $sourceProfile);
            
            $this->createPoints($eventService, $user, $date, ($runDistances * 0.621371), $sourceProfile,"run");
            $this->createPoints($eventService, $user, $date, ($walkDistances * 0.621371), $sourceProfile,"walk");
           
            $sopifyRepository->updateStatus($user->email,true);
            
            $hubspotStatus = $hubspotService->existsOrCreate($user);
            
            $sopifyRepository->updateStatus($user->email,false, $hubspotStatus);
        
        }
        
        http_response_code(204);
        exit();
    }
    
    private function createPoints($eventService, $user, $date, $distance, $sourceProfile, $modality = 'other'){
        
        if(!$distance){
            return false;
        }
        
        $currentDate = Carbon::now()->format('Y-m-d');
        
        $participations = $user->participations()->whereHas('event', function($query) use($currentDate){
            return $query->where('start_date','<=', $currentDate)->where('end_date','>=', $currentDate);
        })->get();
    
        foreach($participations as $participation){

            $pointdata = ['amount' => $distance,'date' => $date,'event_id' => $participation->event_id,'modality' => $modality,'data_source_id' => $sourceProfile->data_source_id]; 
              
            $userPoint = $user->points()->where(['date' => $date,'modality' => $modality,'event_id' => $participation->event_id,'data_source_id' => $sourceProfile->data_source_id])->first();
    
            if($userPoint) {
                $userPoint->update($pointdata);
            } else{
                 $user->points()->create($pointdata);
            }
            
            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $date);
        }
        
        return true;
    }
    
    public function fitBitUserDistanceTracker(EventService $eventService){
        
        $datasource = DataSource::where('short_name', 'fitbit')->first();
       
        $trackerCron = TrackerCron::where('data_source_id',$datasource->id)->first();
        
        if($trackerCron) {
            $totalDuration = Carbon::now()->diffInMinutes($trackerCron->updated_at);
              if($trackerCron->status == 'in_progress' && $totalDuration < 15){
                return response()->json([],200);
              }
              
              $trackerCron->fill(['status' => 'in_progress','last_user_id' => $trackerCron->status =='completed'?0:$trackerCron->last_user_id])->save();
        } else {
            $trackerCron = TrackerCron::create(['data_source_id' => $datasource->id,'last_user_id' => 0,'status' => 'in_progress']);
        }
        
        $date = Carbon::now()->subDay(1)->format('Y-m-d');
        //$date = Carbon::now()->format('Y-m-d');
        
        $cacheKey = "fitbit_distance_".$date;
        
        Cache::forever($cacheKey, uniqid());
    //    Cache::forget($cacheKey);
        if (Cache::has($cacheKey)) {
            // return response()->json([],200);
        }
        
        
         $this->findDistanceTracker($eventService,$date,$trackerCron);
        Cache::forget($cacheKey);
          return response()->json([],200);
    }
    
    private function findDistanceTracker($eventService,$date, $trackerCron,$page = 1, $limit = 10){
        $profiles = DataSourceProfile::where('data_source_id',$trackerCron->data_source_id)
        ->where('id','>',$trackerCron->last_user_id)
        ->orderBy('id', 'ASC')
       // ->where('user_id', 36085)
       //->where('user_id',165232)
        ->forPage($page,$limit)->get();
                //->take(2)->toRawSql();->where('user_id', 36085) 
       
        if(!$profiles->count()) {
            $trackerCron->fill(['status'=> 'completed','last_user_id' => 0])->save();
            return false;
        }
        
        foreach($profiles as $profile) {
         
         $accessToken = $profile->access_token;
         
         if(Carbon::parse($profile->token_expires_at)->lt(Carbon::now())){
                $userSourceProfile = $this->fitbitRefreshToken($profile);
               
                if($userSourceProfile) {
                    $accessToken = $userSourceProfile->access_token;
                }
            }
            
            $trackerCron->fill(['last_user_id'=> $profile->id])->save();
         
         try{
              //
              // $accessToken = "eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyM1BWVzgiLCJzdWIiOiIzNzI1R1AiLCJpc3MiOiJGaXRiaXQiLCJ0eXAiOiJhY2Nlc3NfdG9rZW4iLCJzY29wZXMiOiJ3aHIgd3BybyB3bnV0IHdzbGUgd3dlaSB3c2V0IHdhY3QiLCJleHAiOjE3MzM1MjcxOTYsImlhdCI6MTczMzQ5ODM5Nn0.bdD_Iqib0hBF1XfAj5hPurNYFp7qbRIfuOaJi_sKRxg";
               $httpClient = new Client([
                'base_uri' => 'https://api.fitbit.com/1/',
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $accessToken),
                    'Accept' => 'application/json',
                ],
            ]);
            
          //  dd($accessToken == $profile->access_token);
            //echo $accessToken,'<br>';
            
          //  echo $profile->access_token;
            
            $response = $httpClient->get("user/-/activities/distance/date/{$date}/1d.json");
            
            
            $data = json_decode($response->getBody()->getContents(), true)['activities-distance'];
            $distance = collect($data)->pluck('value')->first();
            $this->createOrUpdateUserProfilePoint($profile->user,$distance,$date,$profile,'cron');
            if(!$distance) {
                $distance = 0;
            }
            
            $distance = $distance * 0.621371;
         
            $this->createPoints($eventService, $profile->user, $date, $distance, $profile);

            } catch(Exception $e){
               $this->fitbitRefreshToken($profile);
               //dd($profile);
                
                Log::stack(['single'])->debug("Fitbit Distance ERROR",['message' => $e->getMessage()]);
              //  dd($e,$profile->access_token);
                //Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - access token not found",['e'=>$e->getMessage()]);
                continue;
            }
        }
        
        $this->findDistanceTracker($eventService,$date,$trackerCron, $page+1, $limit);
    }
    
    private function fitbitRefreshToken($profile){
        
        $response = Http::asForm()
        ->withHeaders([
            'Authorization' => 'Basic '.base64_encode(env('FITBIT_CLIENT_ID').':'.env('FITBIT_CLIENT_SECRET'))
        ])
        ->post('https://api.fitbit.com/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $profile->refresh_token
        ]);
         
        $data = json_decode($response->body(),true);
        
        if(isset($data['access_token'])) {
            $profileData = collect($data)->only(['access_token','refresh_token'])->toArray();
            $profileData['token_expires_at'] = Carbon::now()->addSeconds($data['expires_in'])->format('Y-m-d H:i:s');
        
            $profile->fill($profileData)->save();
            return $profile;
        }
        
        return false;
    }
    
    private function createOrUpdateUserProfilePoint($user, $distance, $date, $sourceProfile, $type='webhook'){
        
        $profilePoint = $user->profilePoints()->where('date', $date)->where('data_source_id', $sourceProfile->data_source_id)->first();
        
        $data = [
            "{$type}_distance_km" => $distance,
            "{$type}_distance_mile" => ($distance * 0.621371),
            'date' => $date,
            'data_source_id' => $sourceProfile->data_source_id
        ];
        
        if($profilePoint) {
        $profilePoint->fill($data)->save();
        } else {
            $user->profilePoints()->create($data);
        }
        
        //
    }
    
    public function testfitbit(){
        
        $sourceProfile = DataSourceProfile::where('user_id',165232)->first();
        try{
         $httpClient = new Client([
                'base_uri' => 'https://api.fitbit.com/1/',
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyMkQzTVMiLCJzdWIiOiIzREpGWTYiLCJpc3MiOiJGaXRiaXQiLCJ0eXAiOiJhY2Nlc3NfdG9rZW4iLCJzY29wZXMiOiJyYWN0IiwiZXhwIjoxNzM0MTIwODcxLCJpYXQiOjE3MzQwOTIwNzF9.aYfkIHRDN0ht0MQdYotj44vcPRmwu4CTsHg68H2xZVU'),//$sourceProfile->access_token),
                    'Accept' => 'application/json',
                ],
            ]);
            
           // $date = $notification['date'];
           
           $modalities = ['run','walk'];//,'swim','bike'];
           
           $otherModalities = ['swim','bike'];
           
           $date = Carbon::parse("2024-02-02")->subDays(0)->format('Y-m-d');
           
            $distanceResponse = $httpClient->get("user/-/activities/distance/date/{$date}/1d.json");
             $distanceResponse = json_decode($distanceResponse->getBody()->getContents(), true)['activities-distance'];
            $totalDistance = collect($distanceResponse)->pluck('value')->first();
           
            $response = $httpClient->get("user/-/activities/date/{$date}.json");

            $data = json_decode($response->getBody()->getContents(), false);
         
            $distances = collect($data->summary->distances)->filter(function($item) use($modalities){ 
                return in_array(strtolower($item->activity),$modalities); 
            }); 
return [$totalDistance,$data];
            $otherm = $totalDistance - $distances->sum('distance');
            
            dd($totalDistance, $otherm, $distances->sum('distance'));
            foreach($distances as $modilityDistance){
                dd($modilityDistance);
            }
            
            /*$distance = collect($data->summary->distances)->filter(function($distance){
                return in_array($distance->activity, ['total','tracker','loggedActivities']);
            //})->sum('distance');
            })->sum('distance');*/
            
            } catch(Exception $e){
                $this->fitbitRefreshToken($sourceProfile);
                dd($e);
                //Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - access token not found",['e'=>$e->getMessage()]);
           
            }
           
    }
}
