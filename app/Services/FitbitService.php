<?php

namespace App\Services;

use App\Interfaces\DataSource;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

use App\Traits\CalculateDays;

class FitbitService implements DataSource
{

    use CalculateDays;

    private $apiUrl;
    private $accessToken;

    private $clientId;

    private $redirectUrl;

    private $clientSecret;

    private $authUrl = "https://www.fitbit.com/oauth2/authorize";
    private $authTokenUrl = "https://api.fitbit.com/oauth2/token";

    private $activityBaseUrl = 'https://api.fitbit.com/1/';
    private $fitbitWebhookVerificationCode;
    private $authResponse;

    private $startDate;
    private $endDate;

    private $dateDays;

    public function __construct($accessToken = null)
    {
        $this->accessToken = $accessToken;

        $this->clientId = config('services.fitbit.client_id');
        $this->redirectUrl = config('services.fitbit.redirect_url');
        $this->clientSecret = config('services.fitbit.client_secret');
        $this->fitbitWebhookVerificationCode = config('services.fitbit.webhook_verification_code');
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

    public function authUrl()
    {


        return $this->authUrl . "?" . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'scope' => 'activity heartrate profile nutrition settings sleep weight'
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

    public function verifyWebhook($code)
    {
        if ($code === $this->fitbitWebhookVerificationCode) {
            return http_response_code(204);
        }
        return http_response_code(404);
    }

    public function refreshToken($refreshtoken = null)
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
            ])
            ->post($this->authTokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshtoken
            ]);

        $data = json_decode($response->body(), true);

        if (isset($data['access_token'])) {
            $profileData = collect($data)->only(['access_token', 'refresh_token'])->toArray();
            $profileData['token_expires_at'] = Carbon::now()->addSeconds($data['expires_in'])->format('Y-m-d H:i:s');

            return $profileData;
        }

        return null;
    }

    public function response()
    {
        return $this->authResponse;
    }

    public function setDate($startDate, $endDate = null)
    {
        list($startDate, $endDate, $dateDays) = $this->daysFromStartEndDate($startDate, $endDate);

        $this->startDate = $startDate;

        $this->endDate = $endDate;

        $this->dateDays = $dateDays;

        return $this;
    }

    function activities()
    {
        $data = [];

        if ($this->dateDays) {
            for ($day = 0; $day <= $this->dateDays; $day++) {
                $items = $this->findActivities($this->startDate->addDays($day)->format('Y-m-d'));
                $data = array_merge($data, $items);
            }
        } else {
            $items = $this->findActivities($this->startDate->format('Y-m-d'));
            $data = array_merge($data, $items);
        }

        return collect($data);
    }

    private function findActivities($date)
    {
        $response = Http::baseUrl($this->activityBaseUrl)
            ->withToken($this->accessToken)
            ->get(sprintf('user/-/activities/date/%s.json', $date));

        if ($response->successful()) {
            $activities = collect($response->json('activities'));
        } else {
            $activities = collect([]);
        }

        $activities = $activities->map(function ($item) {
            $modality = $this->modality($item['name']);
            $date = $item['startDate'];
            $distance = $item['distance'] * 0.621371;
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
