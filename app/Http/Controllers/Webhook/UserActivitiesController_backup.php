<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Models\{
    DataSourceProfile,
    EventParticipation
};
use App\Services\{
    EventService,
    MailService
};
use Exception;

class UserActivitiesController_backup extends Controller
{
    public function userDistanceTracker(Request $request, EventService $eventService){
      
       $profiles = DataSourceProfile::whereHas('source', function ($query) {
            return $query->where('short_name', 'fitbit');
        })
           // ->whereNotNull('sync_start_date')
            ->where(function ($query) {
                return $query->whereNull('last_run_at')->orWhere('last_run_at', '<=', Carbon::now()->subHours(24));
            })
            ->whereHas('user.participations', function ($query) {
                $query->where('subscription_end_date', '>=', Carbon::now()->format('Y-m-d'))
                    ->whereHas('event', function ($eventQuery) {
                        $eventQuery->where('open', true);
                    });
            })
            ->get();
        
       // dd($profiles->count());
        
        foreach($profiles as $profile){
            
            $accessToken = $profile->access_token;
            
            if(Carbon::parse($profile->token_expires_at)->lte(Carbon::now())){
                $userSourceProfile = $this->fitbitRefreshToken($profile);
                
                Log::info('UserActivies: Access token expired for user ID: ' . $profile->user_id . ', refreshing token.');
                
                if($userSourceProfile) {
                    $accessToken = $userSourceProfile->access_token;
                     Log::info('UserActivies: Token refreshed successfully for user ID: ' . $profile->user_id);
                } else {
                     Log::error('UserActivies: Failed to refresh token for user ID: ' . $profile->user_id);
                    continue;
                }
            }
           
         
         try{
              /* $httpClient = new Client([
                'base_uri' => 'https://api.fitbit.com/1/',
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $accessToken),
                    'Accept' => 'application/json',
                ],
            ]);*/
            
            $startDate = $profile->sync_start_date?$profile->sync_start_date:Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
     // $accessToken = "eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyMkQzTVMiLCJzdWIiOiIyMlZUNk0iLCJpc3MiOiJGaXRiaXQiLCJ0eXAiOiJhY2Nlc3NfdG9rZW4iLCJzY29wZXMiOiJyYWN0IHJzZXQgcndlaSBybnV0IHJwcm8gcnNsZSIsImV4cCI6MTczOTMxNzAwMiwiaWF0IjoxNzM5Mjg4MjAyfQ.NUAuavHQLdkemAldBRXe7nj8m3zanEU6382LkCK7s-k";
            $response = Http::withToken($accessToken)->get("https://api.fitbit.com/1/user/-/activities/distance/date/{$startDate}/{$endDate}.json");
           
           if($response->unauthorized()) {
               Log::error('UserActivies: Unauthorized: ' . $profile->user_id,['data' => $response->body()]);
               continue;
           }
            //$response = $httpClient->get("user/-/activities/distance/date/{$startDate}/{$endDate}.json");
            // dd($test->unauthorized(),$test->failed(),$test->json('activities-distance'));//,$response->getBody()->getContents());
            
            //$dateDistances = json_decode($response->getBody()->getContents(), true)['activities-distance'];
            
            $dateDistances = $response->json('activities-distance');
        
            if(!$dateDistances) {
                $dateDistances = [];
            }
            
            if(!count($dateDistances)){
                 continue;
            }
      
            foreach($dateDistances as $data) {
               
                $distance = $data['value'];
                $date = $data['dateTime'];
                
                $this->createOrUpdateUserProfilePoint($profile->user,$distance,$date,$profile,'cron','manual');
                if(!$distance) {
                    $distance = 0;
                }
                
                $distance = $distance * 0.621371;
                try{
                $this->createPoints($eventService, $profile->user, $date, $distance, $profile);
                } catch(Exception $e){
                               Log::error("UserActivies: Error creating points for user ID: " . $profile->user_id . " on " . $date . ": " . $e->getMessage());
                }
            }
            
            $profile->fill(['last_run_at' => Carbon::now()])->save();

            } catch(Exception $e){
                 Log::error("UserActivies: Error fetching Fitbit data for user ID: " . $profile->user_id . ": " . $e->getMessage());
                $this->fitbitRefreshToken($profile);
            }
        }
    }
    
    private function fitbitRefreshToken($profile){
        
         Log::info('UserActivies: Attempting to refresh Fitbit token for user ID: ' . $profile->user_id);

        
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
        
        Log::error('UserActivies: Failed to refresh Fitbit token for user ID: ' . $profile->user_id . '. Response: ' . $response->body()); 
        
        return false;
    }
    
    private function createOrUpdateUserProfilePoint($user, $distance, $date, $sourceProfile, $type='webhook',$actionType="auto"){
        
        $profilePoint = $user->profilePoints()->where('date', $date)->where('data_source_id', $sourceProfile->data_source_id)->first();
        
        $data = [
            "{$type}_distance_km" => $distance,
            "{$type}_distance_mile" => ($distance * 0.621371),
            'date' => $date,
            'data_source_id' => $sourceProfile->data_source_id,
            'action_type' => $actionType
        ];
        
        if($profilePoint) {
        $profilePoint->fill($data)->save();
        } else {
            $user->profilePoints()->create($data);
        }
    }
    
      
    private function createPoints($eventService, $user, $date, $distance, $sourceProfile){
        
        if(!$distance){
            return false;
        }
        
        $currentDate = $date;//Carbon::now()->format('Y-m-d');
        
        $participations = $user->participations()->where('subscription_end_date','>=',$currentDate)->whereHas('event', function($query) use($currentDate){
            return $query->where('start_date','<=', $currentDate);
        })->get();
    
        foreach($participations as $participation){

            $pointdata = ['amount' => $distance,'date' => $date,'event_id' => $participation->event_id,'modality' => 'other','data_source_id' => $sourceProfile->data_source_id]; 
              
            $userPoint = $user->points()->where(['date' => $date,'modality' => 'other','event_id' => $participation->event_id,'data_source_id' => $sourceProfile->data_source_id])->first();
    
            if($userPoint) {
                $userPoint->update($pointdata);
            } else{
                 $user->points()->create($pointdata);
            }
            
            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $date);
        }
        
        return true;
    }
    
    public function triggerCelebrationMail(MailService $mailService){
        $currentDate = Carbon::now()->format('Y-m-d');
        
        $participations = EventParticipation::where('subscription_end_date','>=',$currentDate)->whereHas('event', function($query) use($currentDate){
            return $query->where('start_date','<=', $currentDate);
        })->get();
    
        foreach($participations as $participation){
           // Http::post(sprintf('https://staging-tracker.runtheedge.com/api/v1/send_celebration_mail?event_id=%s&user_id=%s',$participation->event_id, $participation->user_id));
                 $mailService->sendCelebrationMail($participation->event_id, $participation->user_id);    
            //$data = json_decode($response->body(),true);
        }
    }
   // https://staging-tracker.runtheedge.com/api/v1/send_celebration_mail?event_id=64&user_id=165219
}
