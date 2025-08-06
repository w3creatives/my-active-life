<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use App\Models\{
    DataSourceProfile,
    EventParticipation
};
use App\Services\{
    EventService,
    MailService
};
use Exception;

/** This is the controller for FitBit CRON and celebration emails **/

class UserActivitiesController extends Controller
{
    public function userDistanceTracker(Request $request, EventService $eventService)
    {
        Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'Initiated']);

        $currentDate = Carbon::now()->format('Y-m-d');

        $profiles = DataSourceProfile::where('data_source_id', 2)
            //->where('user_id', 112145)
            ->where(function ($query) use($currentDate){
                return $query->whereNull('last_run_at')
                    ->orWhere('last_run_at', '<=', $currentDate);
            })
            ->whereHas('user.participations', function ($query) use($currentDate){
                $query->where('subscription_end_date', '>=', $currentDate)->where('subscription_start_date', '<=', $currentDate);
            })

            ->get();

        Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'PROFILES COUNT','count' => $profiles->count()]);

        foreach ($profiles as $profile) {

            $user = $profile->user;

            $accessToken = $profile->access_token;

            Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'VERIFYING USER TOKEN','userId' => $profile->user_id]);

            if (Carbon::parse($profile->token_expires_at)->lte(Carbon::now())) {
                $userSourceProfile = $this->fitbitRefreshToken($profile);

                Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'USER TOKEN EXPIRED, REFRESHING TOKEN','userId' => $profile->user_id]);

                if ($userSourceProfile) {
                    $accessToken = $userSourceProfile->access_token;
                    Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'USER TOKEN REFRESHED','userId' => $profile->user_id]);
                } else {
                    //$profile->fill(['last_run_at' => Carbon::now()])->save();
                    Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'USER TOKEN COULD NOT BE REFRESHED, SKIPPING','userId' => $profile->user_id]);
                    continue;
                }
            }


            try {

                $startDate = $profile->cron_start_date??$profile->sync_start_date??Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');

                //$startDate = $profile->cron_start_date??"2025-07-20";
                //$endDate = "2025-07-25";


                $startDate = CarbonImmutable::parse($startDate);
                $endDate = $endDate ? CarbonImmutable::parse($endDate) : $startDate;
                $dateDays = $startDate->diffInDays($endDate, true);

                Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'FETCHING RECORDS FOR DATERANGE','userId' => $profile->user_id,'startDate' => $startDate, 'endDate' => $endDate,'totalDays' => $dateDays]);

                for ($day = 0; $day <= $dateDays; $day++) {
                    $totalDistance = 0;

                    $activities = $this->findActivities($profile->access_token, $startDate->addDays($day)->format('Y-m-d'));
                    Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'FETCHED RECORDS FOR DATE','userId' => $profile->user_id,'date' => $startDate->addDays($day)->format('Y-m-d'), 'totalActivities' => count($activities)]);

                    $profile->fill(['cron_start_date' => $startDate->addDays($day)->format('Y-m-d')])->save();

                    if(!$activities) {
                        Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'NO ACTIVITY FOUND, SKIPPING DATE','userId' => $profile->user_id,'date' => $startDate->addDays($day)->format('Y-m-d')]);

                        continue;
                    }

                    foreach($activities as $activity) {
                        $totalDistance += $activity['raw_distance'];

                        Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'Adding Activity','userId' => $profile->user_id,'activity'=>$activity]);


                        $this->createPoints($eventService, $profile->user, $activity['date'], $activity['distance'], $profile,$activity['modality']);
                    }

                    $this->createOrUpdateUserProfilePoint($profile->user, $totalDistance, $startDate->addDays($day)->format('Y-m-d'), $profile, 'cron', 'manual');
                }

                $lastRunAt = Carbon::now();

                Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'PREPARING LAST RUN UPDATE','userId' => $profile->user_id,'lastRunAt' => $lastRunAt]);

                $lastRunUpdated = $profile->fill(['last_run_at' => Carbon::now()])->save();

                Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'LAST RUN UPDATE STATUS','userId' => $profile->user_id,'status' => $lastRunUpdated?'UPDATED':'NOT UPDATED']);


                //$profile->fill(['last_run_at' => Carbon::now()])->save();
            } catch (Exception  $e) {
                //dd($e->getResponse()->getHeader('Fitbit-rate-limit-reset'),$e->getResponse()->getHeader('Fitbit-rate-limit-remaining'),$e->getResponse()->getReasonPhrase(), $e->getMessage());
                Log::channel('custom')->error("USERDISTANCETRACKER: Error fetching Fitbit data for user ID: ",['userId' => $profile->user_id, 'error' => $e->getMessage()]);

                if($e->getCode() != 429){
                    $this->fitbitRefreshToken($profile);
                }
            }
        }
    }

    private function fitbitRefreshToken($profile)
    {

        Log::channel('custom')->info('UserActivies: Attempting to refresh Fitbit token for user ID: ' . $profile->user_id);


        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode(env('FITBIT_CLIENT_ID') . ':' . env('FITBIT_CLIENT_SECRET'))
            ])
            ->post('https://api.fitbit.com/oauth2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $profile->refresh_token
            ]);

        $data = json_decode($response->body(), true);

        if (isset($data['access_token'])) {
            $profileData = collect($data)->only(['access_token', 'refresh_token'])->toArray();
            $profileData['token_expires_at'] = Carbon::now()->addSeconds($data['expires_in'])->format('Y-m-d H:i:s');

            $profile->fill($profileData)->save();
            return $profile;
        }

        Log::channel('custom')->error('UserActivies: Failed to refresh Fitbit token for user ID: ' . $profile->user_id . '. Response: ' . $response->body());

        return false;
    }

    private function createOrUpdateUserProfilePoint($user, $distance, $date, $sourceProfile, $type = 'webhook', $actionType = "auto")
    {

        $profilePoint = $user->profilePoints()->where('date', $date)->where('data_source_id', $sourceProfile->data_source_id)->first();

        $data = [
            "{$type}_distance_km" => $distance,
            "{$type}_distance_mile" => ($distance * 0.621371),
            'date' => $date,
            'data_source_id' => $sourceProfile->data_source_id,
            'action_type' => $actionType
        ];

        if ($profilePoint) {
            $profilePoint->fill($data)->save();
        } else {
            $user->profilePoints()->create($data);
        }
    }


    private function createPoints($eventService, $user, $date, $distance, $sourceProfile, $modality='other')
    {

        if (!$distance) {
            Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'NO DISTANCE FOUND','userId' => $user->id,'date' => $date,'distance' =>$distance,'modality' =>$modality]);

            return false;
        }

        $currentDate = $date; //Carbon::now()->format('Y-m-d');

        $participations = $user->participations()->where('subscription_end_date', '>=', $currentDate)
            ->where('subscription_start_date', '<=', $currentDate)
            ->get();


        Log::channel('custom')->error('USERDISTANCETRACKER:', ['message' => 'VERIFYING PARTICIPATION','userId' => $user->id,'date' => $date,'participations' => $participations->count()]);

        foreach ($participations as $participation) {

            if(!$participation->isModalityOverridden($modality)){
                continue;
            }

            $pointdata = ['amount' => $distance, 'date' => $date, 'event_id' => $participation->event_id, 'modality' => $modality, 'data_source_id' => $sourceProfile->data_source_id];

            $userPoint = $user->points()->where(['date' => $date, 'modality' => $modality, 'event_id' => $participation->event_id, 'data_source_id' => $sourceProfile->data_source_id])->first();

            if ($userPoint) {
                $pointdata['updated_at'] = Carbon::now();
                $userPoint->update($pointdata);
            } else {
                $user->points()->create($pointdata);
            }

            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $date);
            $eventService->userPointWorkflow($user->id, $participation->event_id);
        }

        return true;
    }

    public function triggerCelebrationMail(MailService $mailService)
    {
        $currentDate = Carbon::now()->format('Y-m-d');

        $participations = EventParticipation::where('subscription_end_date', '>=', $currentDate)->whereHas('event', function ($query) use ($currentDate) {
            return $query->where('start_date', '<=', $currentDate);
        })->get();

        foreach ($participations as $participation) {
            // Http::post(sprintf('https://staging-tracker.runtheedge.com/api/v1/send_celebration_mail?event_id=%s&user_id=%s',$participation->event_id, $participation->user_id));
            $mailService->sendCelebrationMail($participation->event_id, $participation->user_id);
            //$data = json_decode($response->body(),true);
        }
    }
    // https://staging-tracker.runtheedge.com/api/v1/send_celebration_mail?event_id=64&user_id=165219

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

        $oneDayresponse = $httpClient->get("user/-/activities/distance/date/{$date}/1d.json");

        $oneDayResult = json_decode($oneDayresponse->getBody()->getContents(), true);

        $totalDistance = $oneDayResult['activities-distance'][0]['value']??0;

//	dd($oneDayresponse,$oneDayResult,$totalDistance);
        /*
        $totalDistance = $distances->filter(function ($distance) {
            return $distance['activity'] == 'total';
        })->sum('distance');


        $loggedDistance = $distances->filter(function ($distance) {
            return $distance['activity'] == 'loggedActivities';
        })->sum('distance');
        */

        //$otherDistance = $totalDistance - $loggedDistance;

        Log::channel('custom')->debug("USERDISTANCETRACKER: - Calculate",compact('activities','date'));




        $fitbitMileConversion = 0.621371;

        $activities = $activities->map(function ($item) use($fitbitMileConversion){
            try{
                $modality = $this->modality($item['name']);
                $date = $item['startDate'];
                $distance = $item['distance'] * $fitbitMileConversion;
                $raw_distance = $item['distance'];

                return compact('date', 'distance', 'modality', 'raw_distance');
            } catch(\Exception $e){
                \Log::channel('custom')->debug("USERDISTANCETRACKER: Fitbit Activity Error - Cron: ", ['item' => $item,'error' => $e->getMessage()]);
            }
        })->reject(function ($item) {
            return $item === null;
        });

        $otherDistance = $totalDistance - $activities->sum('raw_distance');

        Log::channel('custom')->debug("USERDISTANCETRACKER: - Total and other distance",compact('totalDistance','otherDistance'));

        if($otherDistance > 0) {
            $activities = $activities->push(['date' => $date, 'distance' => $otherDistance  * $fitbitMileConversion,'modality' => 'other', 'raw_distance' => $otherDistance]);
            Log::channel('custom')->debug("USERDISTANCETRACKER: - Adding other activities",compact('activities'));
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

        Log::channel('custom')->debug("USERDISTANCETRACKER: - Fitbit Activities All GROUP BY Modality",collect($items)->values()->toArray());

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
            default => 'other',
        };
    }
}
