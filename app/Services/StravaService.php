<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

use App\Interfaces\DataSource;

use App\Traits\CalculateDays;

class StravaService implements DataSource
{
    use CalculateDays;

    private $apiUrl;

    private $accessToken;

    private $clientId;

    private $redirectUrl;

    private $clientSecret;

    private $authUrl = "https://www.strava.com/oauth/authorize";

    private $authTokenUrl = "https://www.strava.com/oauth/token";

    private $authResponse;

    private $startDate;
    private $endDate;

    private $dateDays;

    public function __construct($accessToken = null)
    {
        $this->accessToken = $accessToken;

        $this->apiUrl = config('services.strava.api_url');
        $this->clientId = config('services.strava.client_id');
        $this->redirectUrl = config('services.strava.redirect_url');
        $this->clientSecret = config('services.strava.client_secret');
    }

     public function setDate($startDate, $endDate = null)
    {
        list($startDate, $endDate, $dateDays) = $this->daysFromStartEndDate($startDate, $endDate);

        $this->startDate = $startDate;

        $this->endDate = $endDate;

        $this->dateDays = $dateDays;

        return $this;
    }

    public function modality($modality)
    {
        return match ($modality) {
            'Run', 'VirtualRun' => 'run',
            'Walk' => 'walk',
            'EBikeRide', 'MountainBikeRide', 'EMountainBikeRide', 'GravelRide', 'Handcycle', 'Ride', 'VirtualRide' => 'bike',
            'Swim' => 'swim',
            'Elliptical', 'Hike', 'StairStepper', 'Snowshoe' => 'other',
            default => 'daily_steps',
        };
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setAccessTokenSecret($accessTokenSecret){
        return $this;
    }

    public function authUrl($state = 'web')
    {
        return $this->authUrl."?" . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'scope' => 'read,activity:read',
            'approval_prompt' => 'auto',
            'state' => $state
        ]);
    }

    public function authorize($code)
    {

        $response = Http::post($this->authTokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->successful()) {
            $this->authResponse = $response->object();
        }

        $this->authResponse = null;

        return $this;
    }

    public function response()
    {
        return $this->authResponse;
    }

    public function refreshToken($refreshToken) {}

    function activities($page = 1)
    {
       
        $startOfDay = $this->startDate->copy()->startOfDay()->timestamp;
        $endOfDay = $this->endDate->endOfDay()->timestamp;
        // dd($date->copy()->startOfDay(), $date->copy()->endOfDay());
        $endpoint = $this->apiUrl . 'athlete/activities';

        //$startOfDay = strtotime('today midnight');
        //$endOfDay = strtotime('tomorrow midnight') - 1;

        $params = [
            'after' => $startOfDay,
            'before' => $endOfDay,
            'per_page' => 30,
            'page' => $page
        ];

        $url = $endpoint;// . '?' . http_build_query($params);
        $response = Http::withToken($this->accessToken)->get($url,$params);
        dd($url,$response->json());
        if ($response->successful()) {
            $activities = collect($response->object());
        } else {
            $activities = collect([]);
        }

        if (!$activities->count()) {
            return $activities;
        }

        return $activities->map(function ($activity) {
            $date = Carbon::createFromTimestamp($activity['startTimeInSeconds'])->format('Y-m-d');
            $distance = round(($activity['distanceInMeters'] / 1609.344), 3);
            $modality = $this->modality($activity['activityType']);

            return compact('date', 'distance', 'modality');
        });
    }

    public function verifyWebhook()
    {
        return true;
    }
}
