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
            default => 'none',
        };
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setAccessTokenSecret($accessTokenSecret)
    {
        return $this;
    }

    public function authUrl($state = 'web')
    {
        return $this->authUrl . "?" . http_build_query([
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

    function activities()
    {

        $data = [];

        if ($this->dateDays) {
            for ($day = 0; $day <= $this->dateDays; $day++) {

                $startOfDay = $this->startDate->addDays($day)->copy()->startOfDay()->timestamp;
                $endOfDay = $this->startDate->addDays($day)->copy()->endOfDay()->timestamp;

                $items = $this->findActivities($startOfDay, $endOfDay, $data);
                $data = $items;
            }
        } else {
            $startOfDay = $this->startDate->copy()->startOfDay()->timestamp;
            $endOfDay = $this->startDate->copy()->endOfDay()->timestamp;

            $items = $this->findActivities($startOfDay, $endOfDay, $data);
            $data = $items;
        }

        return collect($data)->reject(function($item){
            return $item['modality'] == 'none';
        })->values();
    }

    private function findActivities($startOfDay, $endOfDay, $data, $page = 1)
    {
        $params = [
            'after' => $startOfDay,
            'before' => $endOfDay,
            'per_page' => 30,
            'page' => $page
        ];

        $response = Http::withToken($this->accessToken)->get($this->apiUrl . 'athlete/activities?'.http_build_query($params));

        if ($response->successful()) {
            $activities = collect($response->json());

            if ($activities->count()) {
                $page++;
            
                $data = array_merge($data, $activities->toArray());
                return $this->findActivities($startOfDay, $endOfDay, $data, $page);
            }
        }

        $activities = collect($data)->map(function ($activity) {
            if (!isset($activity['start_date'])) {
                return $activity;
            }

            $date = Carbon::parse($activity['start_date'])->format('Y-m-d');
            $distance = round(($activity['distance'] / 1609.344), 3);
            $modality = $this->modality($activity['sport_type']);

            return compact('date', 'distance', 'modality');
        });

        $items = $activities->reduce(function ($data, $item) {

            if (!isset($data[$item['modality']])) {
                $data[$item['modality']] = $item;

                return $data;
            }

            $data[$item['modality']]['distance'] += $item['distance'];

            return $data;
        }, []);

        return collect($items)->values()->toArray();
    }

    public function verifyWebhook($code)
    {
        return http_response_code(204);
    }
}
