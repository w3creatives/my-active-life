<?php

namespace App\Services;

use App\Interfaces\DataSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Exception;
use Carbon\Carbon;

class GarminService  implements DataSource
{
    private $accessToken;

    private $accessTokenSecret;

    private $oauthTimestamp = null;

    private $oauthNonce = null;

    private $signature = null;

    private $oauthToken = null;

    private $oauthVerifier = null;

    private $authResponse = null;

    private $consumerKey;
    private $consumerSecret;

    private $healthApiUrl = 'https://apis.garmin.com/wellness-api/rest';
    private $baseUrl = "https://connectapi.garmin.com/oauth-service/oauth/";
    private $oauthConfirmUrl = 'https://connect.garmin.com/oauthConfirm';
    private $oathCallbackUrl;

    private $startDate;
    private $endDate;

    public function __construct()
    {
        $this->consumerKey = config('services.garmin.consumer_key');
        $this->consumerSecret = config('services.garmin.consumer_secret');
        $this->oathCallbackUrl = config('services.garmin.callback_url');
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setAccessTokenSecret($accessTokenSecret)
    {
        $this->accessTokenSecret = $accessTokenSecret;

        return $this;
    }

    public function authUrl()
    {

        $params = $this->getAuthParams();

        ksort($params);

        $this->createSignature('POST', $this->baseUrl . 'request_token', $params);

        $authHeaders = $this->authHeaders();

        try {
            $response = Http::withHeaders(['Authorization' => $authHeaders])->post($this->baseUrl . 'request_token');

            parse_str($response->body(), $result);

            if (isset($result['oauth_token']) && isset($result['oauth_token_secret'])) {
                // Store token secret in session for later use
                Session::put('garmin_token_secret', $result['oauth_token_secret']);

                // Redirect to Garmin authorization page
                return $this->oauthConfirmUrl . '?' .
                    http_build_query([
                        'oauth_token' => $result['oauth_token'],
                        'oauth_callback' => $this->oathCallbackUrl,
                    ]);
            }

            throw new Exception('Failed to get request token');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function authorize($config)
    {
        list($oauthToken, $oauthVerifier) = $config;

        $this->oauthToken = $oauthToken;
        $this->oauthVerifier = $oauthVerifier;

        $params = $this->getAuthParams();

        ksort($params);

        $this->accessTokenSecret = Session::get('garmin_token_secret');

        // Generate signature
        $this->createSignature('POST', $this->baseUrl . 'access_token', $params);

        $oauthParams['oauth_signature'] = $this->signature;

        $authHeaders = $this->authHeaders();

        $response = Http::withHeaders(['Authorization' => $authHeaders])
            ->post($this->baseUrl . 'access_token');

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

    public function activities($startDate = null, $endDate = null)
    {
        $startTimeInSeconds = Carbon::parse($startDate)->timestamp;
        $endTimeInSeconds = Carbon::parse($endDate)->timestamp;

        $queryParams = [
            'summaryStartTimeInSeconds' => $startTimeInSeconds,
            'summaryEndTimeInSeconds' => $endTimeInSeconds,
        ];

        $oauthParams = $this->getAuthParams();

        $oauthParams['oauth_token'] = $this->accessToken;

        // Merge all parameters for signature generation
        $params = array_merge($queryParams, $oauthParams);

        $backfillUrl = $this->healthApiUrl . '/backfill/dailies';

        // Generate signature
        $this->createSignature('GET', $backfillUrl, $params);

        $oauthParams['oauth_signature'] = $this->signature;

        // Build authorization header
        $authHeader = $this->buildAuthorizationHeader($oauthParams);

        $response = Http::withHeaders(['Authorization' => $authHeader])->get($backfillUrl . '?' . http_build_query($queryParams));

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

    public function verifyWebhook() {}

    private function getTimestamp()
    {
        $this->oauthTimestamp = time();
        return $this;
    }

    private function generateNonce()
    {
        $this->oauthNonce = Str::random(32);
        return $this;
    }

    private function createSignature($method, $url, $params)
    {
        ksort($params);
        $paramString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $baseString = strtoupper($method) . '&' .
            rawurlencode($url) . '&' .
            rawurlencode($paramString);

        $signingKey = rawurlencode($this->consumerSecret) . '&' . rawurlencode($this->accessTokenSecret);

        $this->signature = rawurlencode(
            base64_encode(
                hash_hmac('sha1', $baseString, $signingKey, true)
            )
        );

        return $this;
    }

    private function buildAuthorizationHeader($params)
    {
        $headerParams = [];
        foreach ($params as $key => $value) {
            if (strpos($key, 'oauth_') === 0) {
                $headerParams[] = $key . '="' . $value . '"';
            }
        }
        return 'OAuth ' . implode(', ', $headerParams);
    }

    private function getAuthTimestampNonce()
    {
        $oauthTimestamp = $this->oauthTimestamp;
        $oauthNonce = $this->oauthNonce;

        if (!$oauthTimestamp) {
            $oauthTimestamp = $this->getTimestamp();
        }

        if (!$oauthNonce) {
            $oauthNonce = $this->generateNonce();
        }

        return [$oauthTimestamp, $oauthNonce];
    }

    private function getAuthParams()
    {
        list($oauthTimestamp, $oauthNonce) = $this->getAuthTimestampNonce();

        $params = [
            'oauth_consumer_key' => $this->consumerKey,
        ];
        if ($this->oauthToken) {
            $params['oauth_token'] = $this->oauthToken;
        }

        $params['oauth_nonce'] = $oauthNonce;
        $params['oauth_signature_method'] = 'HMAC-SHA1';
        $params['oauth_timestamp'] = $oauthTimestamp;

        if ($this->oauthVerifier) {
            $params['oauth_verifier'] = $this->oauthVerifier;
        }

        $params['oauth_version'] = '1.0';

        return $params;
    }

    private function authHeaders()
    {

        list($oauthTimestamp, $oauthNonce) = $this->getAuthTimestampNonce();

        $authHeaders = 'OAuth ' . 'oauth_consumer_key="' . urlencode($this->consumerKey) . '", ';

        if ($this->oauthToken) {
            $authHeaders .= 'oauth_token="' . urlencode($this->oauthToken) . '", ';
        }

        $authHeaders .= 'oauth_nonce="' . urlencode($oauthNonce) . '", ' .
            'oauth_signature="' . urlencode($this->signature) . '", ' .
            'oauth_signature_method="HMAC-SHA1", ' .
            'oauth_timestamp="' . $oauthTimestamp . '", ';

        if ($this->oauthVerifier) {
            $authHeaders .= 'oauth_verifier="' . urlencode($this->oauthVerifier) . '", ';
        }
        $authHeaders .= 'oauth_version="1.0"';

        return $authHeaders;
    }
    public function setDate($startDate, $endDate = null){
        $this->startDate = $startDate;

        $this->endDate = $endDate??$startDate;

        return $this;
    }
    private function modality(string $modality): string
    {
        return match ($modality) {
            'RUNNING', 'TRACK_RUNNING', 'STREET_RUNNING', 'TREADMILL_RUNNING', 'TRAIL_RUNNING', 'VIRTUAL_RUN', 'INDOOR_RUNNING', 'OBSTACLE_RUN', 'OBSTACLE_RUNNING', 'ULTRA_RUN', 'ULTRA_RUNNING' => 'run',
            'WALKING', 'CASUAL_WALKING', 'SPEED_WALKING', 'GENERIC' => 'walk',
            'CYCLING', 'CYCLOCROSS', 'DOWNHILL_BIKING', 'INDOOR_CYCLING', 'MOUNTAIN_BIKING', 'RECUMBENT_CYCLING', 'ROAD_BIKING', 'TRACK_CYCLING', 'VIRTUAL_RIDE' => 'bike',
            'SWIMMING', 'LAP_SWIMMING', 'OPEN_WATER_SWIMMING' => 'swim',
            'WALKING', 'CASUAL_WALKING', 'SPEED_WALKING', 'GENERIC' => 'daily_steps',
            'HIKING', 'CROSS_COUNTRY_SKIING', 'MOUNTAINEERING', 'ELLIPTICAL', 'STAIR_CLIMBING' => 'other',
            default => 'daily_steps',
        };
    }
}
