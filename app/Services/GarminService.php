<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\DataSourceInterface;
use App\Models\DataSourceProfile;
use App\Traits\CalculateDaysTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Log;

final class GarminService implements DataSourceInterface
{
    use CalculateDaysTrait;

    private string $accessToken;

    private string $accessTokenSecret = '';

    private ?int $oauthTimestamp = null;

    private ?string $oauthNonce = null;

    private ?string $signature = null;

    private ?string $oauthToken = null;

    private ?string $oauthVerifier = null;

    private array $authResponse;

    private string $consumerKey;

    private string $consumerSecret;

    private string $healthApiUrl = 'https://apis.garmin.com/wellness-api/rest';

    private string $baseUrl = 'https://connectapi.garmin.com/oauth-service/oauth/';

    private string $oauthConfirmUrl = 'https://connect.garmin.com/oauthConfirm';

    private ?string $oathCallbackUrl;

    private array $queryParams = [];

    private string $garminRequestUrl = '';

    private string $requestType = 'summary';

    private CarbonImmutable $startDate;

    private CarbonImmutable $endDate;

    private float $dateDays;

    private string $responseType;

    public function __construct()
    {
        $this->consumerKey = config('services.garmin.consumer_key');
        $this->consumerSecret = config('services.garmin.consumer_secret');
        $this->oathCallbackUrl = config('services.garmin.callback_url');
    }

    public function setSecrets($secrets): self
    {
        [$accessToken, $accessTokenSecret] = $secrets;
        Log::info('Garmin Secrets: '.json_encode($secrets));

        $this->setAccessToken($accessToken);
        $this->setAccessTokenSecret($accessTokenSecret);

        return $this;
    }

    public function setAccessToken($accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setAccessTokenSecret(string $accessTokenSecret): self
    {
        $this->accessTokenSecret = $accessTokenSecret;

        return $this;
    }

    public function authUrl(): string
    {
        $params = $this->getAuthParams();

        ksort($params);

        $this->createSignature('POST', $this->baseUrl.'request_token', $params);

        $authHeaders = $this->authHeaders();

        try {
            $response = Http::withHeaders(['Authorization' => $authHeaders])->post($this->baseUrl.'request_token');
            parse_str($response->body(), $result);

            if (isset($result['oauth_token']) && isset($result['oauth_token_secret'])) {
                // Store token secret in session for later use
                Session::put('garmin_token_secret', $result['oauth_token_secret']);

                // Redirect to Garmin authorization page
                return $this->oauthConfirmUrl.'?'.
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

    public function authorize(array $config): self
    {
        [$oauthToken, $oauthVerifier] = $config;

        $this->oauthToken = $oauthToken;
        $this->oauthVerifier = $oauthVerifier;

        $params = $this->getAuthParams();

        ksort($params);

        $this->accessTokenSecret = Session::get('garmin_token_secret');

        // Generate signature
        $this->createSignature('POST', $this->baseUrl.'access_token', $params);

        $params['oauth_signature'] = $this->signature;

        $authHeaders = $this->buildAuthorizationHeader($params);

        $response = Http::withHeaders(['Authorization' => $authHeaders])
            ->post($this->baseUrl.'access_token');

        if ($response->successful()) {
            parse_str($response->body(), $data);
            $this->authResponse = [
                'access_token' => $data['oauth_token'],
                'access_token_secret' => $data['oauth_token_secret'],
            ];
        } else {
            $this->authResponse = [];
        }

        return $this;
    }

    public function response(): array
    {
        return $this->authResponse;
    }

    public function refreshToken($refreshToken): array
    {
        return [];
    }

    public function setDate($startDate, $endDate = null): self
    {
        if (! is_null($startDate)) {
            [$startDate, $endDate, $dateDays] = $this->daysFromStartEndDate($startDate, $endDate);

            $this->startDate = $startDate;

            $this->endDate = $endDate;

            $this->dateDays = $dateDays;
        }

        return $this;
    }

    public function processWebhook(string $url): self
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        $this->garminRequestUrl = $scheme.'://'.$host.$path;

        $queryParamsString = parse_url($url, PHP_URL_QUERY);
        $queryParams = [];
        parse_str($queryParamsString, $queryParams);

        $this->queryParams = $queryParams;

        return $this;
    }

    public function formatWebhookRequest($request)
    {
        $items = collect([]);

        if ($request->has('dailies') || $request->has('activities')) {
            $items = collect(! empty($request->activities) ? $request->activities : $request->dailies);
        }

        if (! $items->count()) {
            return $items;
        }

        $items = $items->map(function ($item) {

            $userAccessToken = $item['userAccessToken'] ?? null;

            $sourceProfile = DataSourceProfile::where(['access_token' => $userAccessToken])
                ->whereHas('source', function ($query) {
                    return $query->where('short_name', 'garmin');
                })
                ->first();

            $user = $sourceProfile->user ?? null;

            $sourceToken = $sourceProfile ? [$sourceProfile->access_token, $sourceProfile->access_token_secret] : null;

            return (object) [
                'user' => $user,
                'date' => null,
                'sourceProfile' => $sourceProfile,
                'dataSourceId' => $sourceProfile ? $sourceProfile->data_source_id : null,
                'sourceToken' => $sourceToken,
                'webhookUrl' => $item['callbackURL'] ?? null,
                'extra' => [
                    'subscriptionId' => null,
                    'source' => 'garmin',
                    'userId' => $user ? $user->id : null,
                ],
            ];
        });

        return $items->filter(function ($item) {
            return $item->user && $item->sourceProfile && $item->webhookUrl;
        });
    }

    public function setRequestType(string $requestType): self
    {
        if (! in_array($requestType, ['upload', 'summary'])) {
            throw new Exception('request type does not match');
        }

        $endpoint = $requestType === 'summary' ? '/backfill/dailies' : '/activities';

        $this->requestType = $requestType;

        $this->garminRequestUrl = $this->healthApiUrl.$endpoint;

        return $this;
    }

    public function activities($responseType = 'data'): Collection
    {
        $data = [];

        $this->responseType = $responseType;

        if ($this->queryParams && $this->garminRequestUrl) {
            $items = $this->findActivities();
            $data = array_merge($data, $items);

            return $this->formatActivities($data);
        }

        $startOfDay = $this->startDate->copy()->startOfDay()->timestamp;

        $endOfDay = $this->dateDays ? Carbon::now()->timestamp : $this->startDate->copy()->endOfDay()->timestamp;

        $items = $this->findActivities($startOfDay, $endOfDay);

        if ($this->responseType === 'response') {
            return collect($items);
        }

        $data = array_merge($data, $items);

        return $this->formatActivities($data);
    }

    public function findActivities(?int $startTimeInSeconds = null, ?int $endTimeInSeconds = null): array
    {
        if ($this->queryParams) {
            $queryParams = $this->queryParams;
        } else {
            $queryParams = $this->buildParams($startTimeInSeconds, $endTimeInSeconds);
        }

        $backfillUrl = $this->garminRequestUrl ? $this->garminRequestUrl : $this->healthApiUrl.'/backfill/dailies';

        $oauthParams = $this->getAuthParams();

        $oauthParams['oauth_token'] = $this->accessToken;

        // Merge all parameters for signature generation
        $params = array_merge($queryParams, $oauthParams);

        // Generate signature
        $this->createSignature('GET', $backfillUrl, $params);

        $oauthParams['oauth_signature'] = $this->signature;

        // Build authorization header
        $authHeader = $this->buildAuthorizationHeader($oauthParams);

        $response = Http::withHeaders(['Authorization' => $authHeader])
            ->get($backfillUrl, $queryParams);

        Log::info('Garmin Response: '.$response->body());

        if ($this->responseType === 'response') {
            return [$response];
        }

        if ($response->successful()) {
            $activities = collect($response->json());
        } else {
            $activities = collect([]);
        }

        return $activities->map(function ($activity) use ($startTimeInSeconds, $endTimeInSeconds) {
            if (isset($activity['distanceInMeters'])) {
                $date = Carbon::createFromTimestamp($activity['startTimeInSeconds'])->format('Y-m-d');
                $distance = round(($activity['distanceInMeters'] / 1609.344), 3);
                $modality = $this->modality($activity['activityType']);
                $time = $activity['startTimeInSeconds'];
                $raw_distance = round($activity['distanceInMeters'], 3);

                return compact('date', 'distance', 'modality', 'raw_distance');
            }
        })->toArray();

        /*return collect($items)
        ->values()
        ->toArray();*/
    }

    public function formatActivities($activities): Collection
    {
        $items = collect($activities)->reduce(function ($data, $item) {
            if (! $item) {
                return $data;
            }

            if (isset($data[$item['date']])) {
                if ($data[$item['date']]['modality'] === $item['modality']) {
                    $data[$item['date']]['distance'] += $item['distance'];
                } else {
                    $data[$item['date']] = array_merge($data[$item['date']], $item);
                }

                return $data;
            }

            $data[$item['date']] = $item;

            return $data;

            $data[$item['date']][$item['modality']]['distance'] += $item['distance'];

            return $data;

            if (! isset($data[$item['date']][$item['modality']])) {
                $data[$item['date']][$item['modality']] = $item;

                return $data;
            }

            $data[$item['date']][$item['modality']]['distance'] += $item['distance'];

            return $data;
        }, []);

        return collect($items)->values();
    }

    public function activitiesTested()
    {
        $startTimeInSeconds = Carbon::parse($this->startDate)->timestamp - 1;
        $endTimeInSeconds = Carbon::parse($this->startDate)->timestamp;

        $queryParams = [
            // 'summaryStartTimeInSeconds' => $startTimeInSeconds,
            // 'summaryEndTimeInSeconds' => $endTimeInSeconds,
            'uploadStartTimeInSeconds' => $startTimeInSeconds,
            'uploadEndTimeInSeconds' => $endTimeInSeconds,
        ];

        $oauthParams = $this->getAuthParams();

        // $oauthParams = array_merge($oauthParams, $queryParams);

        $oauthParams['oauth_token'] = $this->accessToken;

        // Merge all parameters for signature generation
        $params = array_merge($queryParams, $oauthParams);

        $backfillUrl = $this->healthApiUrl.'/activities';

        // Generate signature
        $this->createSignature('GET', $backfillUrl, $params);

        $oauthParams['oauth_signature'] = $this->signature;

        // Build authorization header
        $authHeader = $this->buildAuthorizationHeader($oauthParams);

        $response = Http::withHeaders(['Authorization' => $authHeader])
            ->get($backfillUrl, $queryParams);

        if ($response->successful()) {
            $activities = collect($response->object());
        } else {
            $activities = collect([]);
        }

        if (! $activities->count()) {
            return $activities;
        }

        $activities = $activities->map(function ($activity) {
            $date = Carbon::createFromTimestamp($activity['startTimeInSeconds'])->format('Y-m-d');
            $distance = round(($activity['distanceInMeters'] / 1609.344), 3);
            $modality = $this->modality($activity['activityType']);
            $raw_distance = round($activity['distanceInMeters'], 3);

            return compact('date', 'distance', 'modality', 'raw_distance');
        });

        $items = $activities->reduce(function ($data, $item) {

            if (! isset($data[$item['modality']])) {
                $data[$item['modality']] = $item;

                return $data;
            }

            $data[$item['modality']]['distance'] += $item['distance'];

            return $data;
        }, []);

        return collect($items)->values()->toArray();
    }

    public function verifyWebhook($code): int
    {
        return http_response_code(204);
    }

    /**
     * Disconnect a user from Garmin
     *
     * @param  string  $accessToken  User's access token
     * @param  string  $accessTokenSecret  User's access token secret
     * @return bool Success status of disconnection
     */
    public function disconnectUser(string $accessToken, string $accessTokenSecret): bool
    {
        try {
            $this->accessToken = $accessToken;
            $this->accessTokenSecret = $accessTokenSecret;

            // Set up OAuth parameters
            $this->setOAuthParameters();

            // Create OAuth request for deregistration
            $url = $this->healthApiUrl.'/user/registration';
            $response = $this->makeOAuthRequest('DELETE', $url);

            // Check if disconnection was successful
            if ($response && ($response->getStatusCode() === 200 || $response->getStatusCode() === 204)) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('GarminService: Failed to disconnect user: '.$e->getMessage());

            return false;
        }
    }

    private function buildParams(int $startTimeInSeconds, int $endTimeInSeconds): array
    {
        return [
            $this->requestType.'StartTimeInSeconds' => $startTimeInSeconds,
            $this->requestType.'EndTimeInSeconds' => $endTimeInSeconds,
        ];
    }

    private function getTimestamp(): self
    {
        $this->oauthTimestamp = time();

        return $this;
    }

    private function generateNonce(): self
    {
        $this->oauthNonce = Str::random(32);

        return $this;
    }

    private function createSignature(string $method, string $url, array $params): self
    {
        ksort($params);
        $paramString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $baseString = mb_strtoupper($method).'&'.
            rawurlencode($url).'&'.
            rawurlencode($paramString);

        $signingKey = rawurlencode($this->consumerSecret).'&';
        if ($this->accessTokenSecret) {
            $signingKey .= rawurlencode($this->accessTokenSecret);
        }
        $this->signature = rawurlencode(
            base64_encode(
                hash_hmac('sha1', $baseString, $signingKey, true)
            )
        );

        return $this;
    }

    private function buildAuthorizationHeader(array $params): string
    {
        $headerParams = [];
        foreach ($params as $key => $value) {
            if (mb_strpos($key, 'oauth_') === 0) {
                $headerParams[] = $key.'="'.$value.'"';
            }
        }

        return 'OAuth '.implode(', ', $headerParams);
    }

    private function getAuthTimestampNonce(): array
    {
        if (! $this->oauthTimestamp) {
            $this->getTimestamp();
        }

        if (! $this->oauthNonce) {
            $this->generateNonce();
        }

        $oauthTimestamp = $this->oauthTimestamp;
        $oauthNonce = $this->oauthNonce;

        return [$oauthTimestamp, $oauthNonce];
    }

    private function getAuthParams(): array
    {
        [$oauthTimestamp, $oauthNonce] = $this->getAuthTimestampNonce();

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

    private function authHeaders(): string
    {
        [$oauthTimestamp, $oauthNonce] = $this->getAuthTimestampNonce();

        $params = [
            'oauth_consumer_key' => urlencode($this->consumerKey),
            'oauth_nonce' => urlencode($oauthNonce),
            'oauth_signature' => $this->signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $oauthTimestamp,
            'oauth_version' => '1.0',
        ];

        return $this->buildAuthorizationHeader($params);
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

    /**
     * Set OAuth parameters for authentication
     */
    private function setOAuthParameters(): void
    {
        $this->oauthTimestamp = time();
        $this->oauthNonce = bin2hex(random_bytes(16));
    }

    /**
     * Make an OAuth request to Garmin API
     *
     * @param  string  $method  HTTP method (GET, POST, DELETE)
     * @param  string  $url  API endpoint URL
     * @param  array  $params  Additional parameters
     * @return \Illuminate\Http\Response|null Response object or null on failure
     */
    private function makeOAuthRequest(string $method, string $url, array $params = []): ?\Illuminate\Http\Response
    {
        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->oauthNonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $this->oauthTimestamp,
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        // Generate signature
        $signatureBaseString = $this->generateSignatureBaseString($method, $url, array_merge($oauthParams, $params));
        $signingKey = $this->consumerSecret.'&'.$this->accessTokenSecret;
        $signature = base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));
        $oauthParams['oauth_signature'] = $signature;

        // Create authorization header
        $authHeader = 'OAuth '.implode(', ', array_map(function ($key, $value) {
            return $key.'="'.rawurlencode($value).'"';
        }, array_keys($oauthParams), $oauthParams));

        // Make the request
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request($method, $url, [
                'headers' => [
                    'Authorization' => $authHeader,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => $params,
            ]);

            return $response;
        } catch (Exception $e) {
            Log::error('GarminService OAuth request failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Generate OAuth signature base string
     */
    private function generateSignatureBaseString(string $method, string $url, array $params): string
    {
        $encodedParams = [];
        foreach ($params as $key => $value) {
            $encodedParams[rawurlencode($key)] = rawurlencode($value);
        }

        ksort($encodedParams);

        $paramString = implode('&', array_map(function ($key, $value) {
            return $key.'='.$value;
        }, array_keys($encodedParams), $encodedParams));

        return mb_strtoupper($method).'&'.rawurlencode($url).'&'.rawurlencode($paramString);
    }
}
