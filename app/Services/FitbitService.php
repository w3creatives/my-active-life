<?php

namespace App\Services;

use App\Interfaces\DataSource;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FitbitService implements DataSource
{

    private $apiUrl;
    private $accessToken;

    private $clientId;

    private $redirectUrl;

    private $clientSecret;

    private $authUrl = "https://www.fitbit.com/oauth2/authorize";
    private $authTokenUrl = "https://api.fitbit.com/oauth2/token";


    private $authResponse;

    public function __construct($accessToken = null)
    {
        $this->accessToken = $accessToken;

        $this->apiUrl = config('services.fitbit.api_url');
        $this->clientId = config('services.fitbit.client_id');
        $this->redirectUrl = config('services.fitbit.redirect_url');
        $this->clientSecret = config('services.fitbit.client_secret');
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

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

    public function verifyWebhook() {}

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

    function activities($date = null)
    {
        $date = is_null($date) ? Carbon::now() : Carbon::parse($date);

        $startOfDay = $date->copy()->startOfDay()->timestamp;
        $endOfDay = $date->copy()->endOfDay()->timestamp;
        // dd($date->copy()->startOfDay(), $date->copy()->endOfDay());
        $endpoint = $this->apiUrl . 'athlete/activities';

        //$startOfDay = strtotime('today midnight');
        //$endOfDay = strtotime('tomorrow midnight') - 1;

        $params = [
            'after' => $startOfDay,
            'before' => $endOfDay,
            'per_page' => 10,
        ];

        $url = $endpoint . '?' . http_build_query($params);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken
        ])->get($url);
        if ($response->successful()) {
            return $response->object();
        }
        return [];
    }
}
