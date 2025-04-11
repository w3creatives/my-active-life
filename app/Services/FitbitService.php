<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;

class FitbitService
{

    private $apiUrl;
    private $accessToken;
    
    private $clientId;
    
    private $redirectUrl;
    
    public function __construct($accessToken = null)
    {
        $this->apiUrl = env('STRAVA_API_BASE_URL');
        $this->accessToken = $accessToken;
        
        $this->clientId = env('STRAVA_CLIENT_ID');
        $this->redirectUrl = env('STRAVA_REDIRECT_URI');
        $this->clientSecret = env('STRAVA_CLIENT_SECRET');
    }
    
    public function setAccessToken($accessToken){
        $this->accessToken = $accessToken;
        
        return $this;
    }
    
    public function authUrl(){
        

         return "https://www.fitbit.com/oauth2/authorize?" . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'scope' => 'activity heartrate profile nutrition settings sleep weight'
        ]);
    }
    
    public function authorize($code){
 
        $response = Http::post('https://www.strava.com/oauth/token',[
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);
        
        if($response->successful()){
            return $response->object();
        }
        
        return false;
    }
    
    function activities($date = null)
    {
         $date = is_null($date)?Carbon::now():Carbon::parse($date);
        
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
            'Authorization' => 'Bearer '. $this->accessToken
        ])->get($url);
        if($response->successful()){
        return $response->object();
        }
        return [];
    }
}