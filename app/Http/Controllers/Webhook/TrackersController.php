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
    HubspotService,
    MailService
};
use App\Repositories\SopifyRepository;
use App\Models\{
    User,
    DataSourceProfile,
    DataSource,
    TrackerCron,
    Event
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
        #Log::stack(['single'])->debug("Fitbit Subscription TEST",['userId' => 34242]);
        $notifications = $request->_json?collect($request->_json):$request->all();
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

            try{
                /*$httpClient = new Client([
                    'base_uri' => 'https://api.fitbit.com/1/',
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $sourceProfile->access_token),
                        'Accept' => 'application/json',
                    ],
                ]);*/

                $date = $notification['date'];

                $activities = $this->findActivities($sourceProfile->access_token, $date);

                if(!$activities) {
                    continue;
                }
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

                //$response = $httpClient->get("user/-/activities/distance/date/{$date}/1d.json");

            } catch(Exception $e){
                $this->fitbitRefreshToken($sourceProfile);
                Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - access token not found",['e'=>$e->getMessage()]);
                continue;
            }
            // $data = json_decode($response->getBody()->getContents(), true)['activities-distance'];
            // $distance = collect($data)->pluck('value')->first();

            /*if(!$distance) {
                $distance = 0;
            }*/

            $totalDistance = 0;

            foreach($activities as $activity) {
                Log::stack(['single'])->debug("Fitbit Webhook",$activity);

                $totalDistance += $activity['raw_distance'];
                Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - Log distance",['distance' => $activity['raw_distance'],'modality' => $activity['modality'],'userId' => $userId]);
                $this->createPoints($eventService, $user, $date, $activity['distance'], $sourceProfile,null, $activity['modality']);
            }

            $this->createOrUpdateUserProfilePoint($user,$totalDistance,$date,$sourceProfile);

            //$this->createOrUpdateUserProfilePoint($user,$distance,$date,$sourceProfile);

            //$distance = $distance * 0.621371;

            //Log::stack(['single'])->debug("Fitbit Subscription - {$notification['subscriptionId']} - Log distance",['distance' => $distance,'userId' => $userId]);



            //$this->createPoints($eventService, $user, $date, $distance, $sourceProfile);

            $sopifyRepository->updateStatus($user->email,true);
            /*
            $hubspotStatus = $hubspotService->existsOrCreate($user);

            $sopifyRepository->updateStatus($user->email,false, $hubspotStatus);
        */
        }

        http_response_code(204);
        exit();
    }

    private function createPoints($eventService, $user, $date, $distance, $sourceProfile, $eventId=null, $modality='other'){

        if(!$distance){
            return false;
        }

        $currentDate = Carbon::now()->format('Y-m-d');

        if($eventId) {
            $participations = $user->participations()->where('event_id', $eventId)->where('subscription_end_date','>=',$date)->whereHas('event', function($query) use($date){
                return $query->where('start_date','<=', $date);
            })->get();

        } else {
            $participations = $user->participations()->where('subscription_end_date','>=',$date)->whereHas('event', function($query) use($date){
                return $query->where('start_date','<=', $date);
            })->get();
        }

        if(!$participations->count()) {
            return false;
        }

        foreach($participations as $participation){

            $pointdata = ['amount' => $distance,'date' => $date,'event_id' => $participation->event_id,'modality' => $modality,'data_source_id' => $sourceProfile->data_source_id];

            $userPoint = $user->points()->where(['date' => $date,'modality' => $modality,'event_id' => $participation->event_id,'data_source_id' => $sourceProfile->data_source_id])->first();

            if($userPoint) {
                $userPoint->update($pointdata);
            } else{
                $user->points()->create($pointdata);
            }

            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $date);
            $eventService->userPointWorkflow($user->id, $participation->event_id);
            (new MailService)->sendCelebrationMail($participation->event_id, $user->id);
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
            //->where('user_id', 156158)
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
                /* $httpClient = new Client([
                  'base_uri' => 'https://api.fitbit.com/1/',
                  'headers' => [
                      'Authorization' => sprintf('Bearer %s', $accessToken),
                      'Accept' => 'application/json',
                  ],
              ]);*/

                //  dd($accessToken == $profile->access_token);
                //echo $accessToken,'<br>';

                //  echo $profile->access_token;

                $activities = $this->findActivities($accessToken, $date);

                if(!$activities) {
                    continue;
                }

                $totalDistance = 0;

                foreach($activities as $activity) {
                    $totalDistance += $activity['raw_distance'];

                    $this->createPoints($eventService, $profile->user, $date, $activity['distance'], $profile,null, $activity['modality']);
                }
                $this->createOrUpdateUserProfilePoint($profile->user,$totalDistance,$date,$profile,'cron');
                //$response = $httpClient->get("user/-/activities/distance/date/{$date}/1d.json");

                /*
                $data = json_decode($response->getBody()->getContents(), true)['activities-distance'];
                $distance = collect($data)->pluck('value')->first();
                $this->createOrUpdateUserProfilePoint($profile->user,$distance,$date,$profile,'cron');
                if(!$distance) {
                    $distance = 0;
                }

                $distance = $distance * 0.621371;

                $this->createPoints($eventService, $profile->user, $date, $distance, $profile);
    */
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

    public function fitBiUserManualDistanceTracker(Request $request, EventService $eventService){
        header("Access-Control-Allow-Origin: *");
        /*
        $response = '{
  "activities-steps": [
    {
      "dateTime": "2018-12-26",
      "value": "2504"
    },
    {
      "dateTime": "2018-12-27",
      "value": "3723"
    },
    {
      "dateTime": "2018-12-28",
      "value": "8304"
    },
    {
      "dateTime": "2018-12-29",
      "value": "7861"
    },
    {
      "dateTime": "2018-12-30",
      "value": "837"
    },
    {
      "dateTime": "2018-12-31",
      "value": "4103"
    },
    {
      "dateTime": "2019-01-01",
      "value": "1698"
    }
  ]
}';

dd(json_decode($response, true));*/

        $datasource = DataSource::where('short_name', 'fitbit')->first();

        $profile = DataSourceProfile::where('data_source_id',$datasource->id)
            ->where('user_id', $request->get('user_id'))

            ->first();

        if(is_null($profile)) {
            //throw new \Exception("NOT FOUND");
            return response()->json(['message'=>'Profile not found'],403);
        }

        $startDate = $request->get('sync_start_date');
        $eventId = $request->get('event_id');

        if($eventId){

            $currentDate = Carbon::now()->format('Y-m-d');
            $eventEndDate = Event::where('id',$eventId)->where('start_date','<=',$startDate)->where('end_date','>=',$startDate)->first();


            if(is_null($eventEndDate)) {
                return response()->json(['message'=>'Event have not started yet or expired'],403);
            }

            $endDate = Carbon::parse($eventEndDate->end_date)->format('Y-m-d');

        } else {
            $endDate = Carbon::now()->format('Y-m-d');
        }


        $accessToken = $profile->access_token;

        if(Carbon::parse($profile->token_expires_at)->lt(Carbon::now())){
            $userSourceProfile = $this->fitbitRefreshToken($profile);

            if($userSourceProfile) {
                $accessToken = $userSourceProfile->access_token;
            }
        }


        try{
            $httpClient = new Client([
                'base_uri' => 'https://api.fitbit.com/1/',
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $accessToken),
                    'Accept' => 'application/json',
                ],
            ]);



            //  createPoints($eventService, $user, $date, $distance, $sourceProfile, $eventId=null){

            $response = $httpClient->get("user/-/activities/distance/date/{$startDate}/{$endDate}.json");

            $dateDistances = json_decode($response->getBody()->getContents(), true)['activities-distance'];

            if(!count($dateDistances)){
                return response()->json(['message'=>'NOT FOUND'],403);
                throw new \Exception("No Data found");
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
                    $this->createPoints($eventService, $profile->user, $date, $distance, $profile,$eventId);
                } catch(Exception $e){
                    Log::stack(['single'])->debug("Miles ERROR",['message' => $e->getMessage()]);
                }
            }



        } catch(Exception $e){

            return response()->json(['message'=>$e->getMessage()],403);
            throw new \Exception($e);
            //$this->fitbitRefreshToken($profile);
            Log::stack(['single'])->debug("Fitbit Distance ERROR",['message' => $e->getMessage()]);
        }

        return response()->json(['sync_start_date' => $startDate],200);
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
            return $distance['activity'] == 'total';
        })->sum('distance');

        $loggedDistance = $distances->filter(function ($distance) {
            return $distance['activity'] == 'loggedActivities';
        })->sum('distance');

        $otherDistance = $totalDistance - $loggedDistance;

        Log::stack(['single'])->debug("Fitbit Activities with Modality",[$activities]);

        Log::stack(['single'])->debug("Fitbit Activities with Modality",compact('loggedDistance','totalDistance','otherDistance'));


        $fitbitMileConversion = 0.621371;

        $activities = $activities->map(function ($item) use($fitbitMileConversion){
            $modality = $this->modality($item['name']);
            $date = $item['startDate'];
            $distance = $item['distance'] * $fitbitMileConversion;
            $raw_distance = $item['distance'];

            return compact('date', 'distance', 'modality', 'raw_distance');
        });



        if($otherDistance > 0) {
            $activities = $activities->push(['date' => $date, 'distance' => $otherDistance  * $fitbitMileConversion,'modality' => 'other', 'raw_distance' => $otherDistance]);
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

        Log::stack(['single'])->debug("Fitbit Activities All GROUP BY Modality",collect($items)->values()->toArray());

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
