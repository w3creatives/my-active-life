<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\DataSourceInterface;
use App\Models\DataSourceProfile;
use App\Traits\CalculateDaysTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for interacting with the Strava API
 * Handles authentication, data retrieval, and webhook processing
 */
final class StravaService implements DataSourceInterface
{
    use CalculateDaysTrait;

    private string $apiUrl;

    private string $accessToken;

    private string $clientId;

    private string $redirectUrl;

    private string $clientSecret;

    private string $authUrl = 'https://www.strava.com/oauth/authorize';

    private string $authTokenUrl = 'https://www.strava.com/oauth/token';

    private array $authResponse;

    private mixed $startDate;

    private mixed $endDate;

    private $dateDays;

    /**
     * Initialize the Strava service with configuration values
     *
     * @param  string  $accessToken  Optional access token for authenticated requests
     */
    public function __construct(string $accessToken = '')
    {
        $this->accessToken = $accessToken;

        $this->apiUrl = config('services.strava.api_url');
        $this->clientId = config('services.strava.client_id');
        $this->redirectUrl = config('services.strava.redirect_url');
        $this->clientSecret = config('services.strava.client_secret');
    }

    /**
     * Set the date range for activity retrieval
     * Uses the CalculateDaysTrait to handle date calculations
     *
     * @param  mixed  $startDate  The start date for data retrieval
     * @param  mixed|null  $endDate  Optional end date for data retrieval
     */
    public function setDate(mixed $startDate, mixed $endDate = null): self
    {
        [$startDate, $endDate, $dateDays] = $this->daysFromStartEndDate($startDate, $endDate);

        $this->startDate = $startDate;

        $this->endDate = $endDate;

        $this->dateDays = $dateDays;

        return $this;
    }

    /**
     * Map Strava activity types to standardized modality categories
     * Consistent with other services like Fitbit and Garmin
     *
     * @param  string  $modality  The Strava activity type
     * @return string The standardized modality category
     */
    public function modality(string $modality): string
    {
        return match ($modality) {
            'Run', 'VirtualRun' => 'run',
            'Walk' => 'walk',
            'EBikeRide', 'MountainBikeRide', 'EMountainBikeRide', 'GravelRide', 'Handcycle', 'Ride', 'VirtualRide' => 'bike',
            'Swim' => 'swim',
            'Elliptical', 'Hike', 'StairStepper', 'Snowshoe' => 'other',
            default => 'daily_steps', // Consistent with other services
        };
    }

    /**
     * Set the access token for authenticated API requests
     *
     * @param  string  $accessToken  The OAuth access token
     */
    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Set the access token secret (used for storing Strava owner_id)
     * Implemented for interface compatibility but not used in Strava
     *
     * @param  string  $accessTokenSecret  The token secret
     */
    public function setAccessTokenSecret(string $accessTokenSecret): self
    {
        return $this;
    }

    /**
     * Generate the OAuth authorization URL for Strava
     * Used to redirect users to Strava for authentication
     *
     * @param  string  $state  Optional state parameter for OAuth flow
     * @return string The complete authorization URL
     */
    public function authUrl(string $state = 'web'): string
    {
        return $this->authUrl.'?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'scope' => 'read,activity:read',
            'approval_prompt' => 'auto',
            'state' => $state,
        ]);
    }

    /**
     * Exchange authorization code for access token
     * Called after user authorizes the application on Strava
     *
     * @param  array  $config  Array containing the authorization code
     */
    public function authorize(array $config): self
    {
        [$code] = $config;

        $response = Http::post($this->authTokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->successful()) {
            $data = $response->object();

            $tokenExpiresAt = Carbon::parse($data->expires_at)->format('Y-m-d H:i:s');

            $this->authResponse = [
                'access_token' => $data->access_token,
                'refresh_token' => $data->refresh_token ?? null,
                'token_expires_at' => $tokenExpiresAt,
                'user_id' => (string) $data->athlete->id,
            ];
        } else {
            $this->authResponse = [];
        }

        return $this;
    }

    /**
     * Get the authorization response data
     * Contains access token, refresh token, and expiration
     *
     * @return array The authorization response data
     */
    public function response(): array
    {
        return $this->authResponse;
    }

    /**
     * Refresh an expired access token using a refresh token
     * Strava tokens expire after 6 hours and must be refreshed
     *
     * @param  string|null  $refreshToken  The refresh token from previous authorization
     * @return array The new token information or error
     */
    public function refreshToken(?string $refreshToken): array
    {
        if (! $refreshToken) {
            return ['error' => 'No refresh token provided'];
        }

        $response = Http::post($this->authTokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            $this->authResponse = [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_expires_at' => Carbon::createFromTimestamp($data['expires_at'])->format('Y-m-d H:i:s'),
            ];

            return $this->authResponse;
        }

        return ['error' => $response->body()];
    }

    /**
     * Retrieve activities for the configured date range
     * Fetches activities day by day and processes them
     *
     * @return Collection Collection of processed activity data
     */
    public function activities(): Collection
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

        return collect($data)->reject(function ($item) {
            return $item['modality'] === 'daily_steps';
        })->values();
    }

    /**
     * Verify webhook subscription from Strava
     * Returns 204 No Content as required by Strava webhook validation
     *
     * @param  string  $code  The verification code from Strava
     * @return int HTTP status code
     */
    public function verifyWebhook($code): int
    {
        return http_response_code(204);
    }

    /**
     * Create a webhook subscription with Strava
     * Similar to the Ruby implementation in StravaSubscriber
     *
     * @return array The subscription response
     */
    public function createSubscription(): array
    {
        try {
            // Use the correct webhook route from settings.php
            $callbackUrl = route('profile.device-sync.webhook', ['sourceSlug' => 'strava'], true); // true forces HTTPS

            // Log the URL we're using
            Log::debug('StravaService: Creating subscription', [
                'callback_url' => $callbackUrl,
                'client_id' => $this->clientId,
            ]);

            if (empty($callbackUrl)) {
                Log::error('StravaService: No callback URL available');

                return ['error' => 'Callback URL is not configured. Please set Strava Subscription URL first.'];
            }

            /**
             * Strava webhook reference documentation
             * @link https://developers.strava.com/docs/webhooks/
             */
            $verifyToken = config('services.strava.webhook_verification_code');

            Log::debug('StravaService: Creating subscription with parameters', [
                'client_id' => $this->clientId,
                'callback_url' => $callbackUrl,
                'verify_token' => $verifyToken,
            ]);

            $response = Http::post('https://www.strava.com/api/v3/push_subscriptions', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'callback_url' => $callbackUrl,
                'verify_token' => $verifyToken,
            ]);

            if ($response->successful()) {
                Log::debug('StravaService: Subscription created successfully', [
                    'response' => $response->json(),
                ]);

                return $response->json();
            }

            Log::error('StravaService: Failed to create subscription', [
                'status' => $response->status(),
                'body' => $response->body(),
                'callback_url' => $callbackUrl,
            ]);

            return ['error' => $response->body()];

        } catch (Exception $e) {
            // Handle "already exists" error similar to Ruby implementation
            if (mb_strpos($e->getMessage(), 'already exists') !== false) {
                Log::info('StravaService: Subscription already exists');

                return ['status' => 'already exists'];
            }

            Log::error('StravaService: Exception creating subscription', [
                'message' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Process webhook event from Strava
     * Similar to the Ruby implementation in StravaDataFetcher
     *
     * @param  array  $data  The webhook payload
     * @return array Processed activity data
     */
    public function processWebhook(array $data): array
    {
        Log::debug('StravaService: Processing webhook', ['data' => $data]);

        // Verify this is an activity creation event
        if (! isset($data['object_type']) || $data['object_type'] !== 'activity' ||
            ! isset($data['aspect_type']) || $data['aspect_type'] !== 'create') {
            return ['status' => 'ignored', 'reason' => 'Not an activity creation event'];
        }

        // Find the user by owner_id (stored in access_token_secret in Ruby implementation)
        $sourceProfile = DataSourceProfile::where('access_token_secret', $data['owner_id'])
            ->whereHas('source', function ($query) {
                return $query->where('short_name', 'strava');
            })
            ->first();

        if (! $sourceProfile) {
            Log::error('StravaService: Unable to find user for owner_id', [
                'owner_id' => $data['owner_id'],
            ]);

            return ['status' => 'error', 'reason' => 'User not found'];
        }

        // Refresh token if needed
        if (Carbon::parse($sourceProfile->token_expires_at)->lt(Carbon::now())) {
            $this->refreshToken($sourceProfile->refresh_token);
            $sourceProfile->refresh();
        }

        // Set access token for API calls
        $this->accessToken = $sourceProfile->access_token;

        // Fetch the activity data
        try {
            $activity = $this->fetchActivity($data['object_id']);

            // Process and store the activity data
            return $this->processActivity($activity, $sourceProfile);
        } catch (Exception $e) {
            Log::error('StravaService: Failed to fetch activity', [
                'message' => $e->getMessage(),
                'activity_id' => $data['object_id'],
            ]);

            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    /**
     * Deauthorize the application with Strava
     * Revokes all tokens and access for the current user
     *
     * @return array Response from the deauthorization request
     */
    public function deauthorize(): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post('https://www.strava.com/oauth/deauthorize', [
                    'access_token' => $this->accessToken,
                ]);

            if ($response->successful()) {
                Log::info('Successfully deauthorized Strava application');

                return $response->json();
            }
            Log::error('Failed to deauthorize Strava application', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['error' => $response->body()];

        } catch (Exception $e) {
            Log::error('Exception deauthorizing Strava application', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Subscribe a user to Strava webhook notifications
     * Required by the DataSourceInterface
     *
     * @param  int  $userId  The user ID
     * @param  string  $sourceUserId  The Strava user ID
     */
    public function subscribe(int $userId, string $sourceUserId): self
    {
        // Create a subscription for this user if needed
        try {
            $this->createSubscription();
        } catch (Exception $e) {
            Log::error('Failed to create Strava subscription during user subscribe', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
        }

        return $this;
    }

    /**
     * Find activities for a specific time range
     * Handles pagination and processes activity data
     *
     * @param  int  $startOfDay  Start timestamp
     * @param  int  $endOfDay  End timestamp
     * @param  array  $data  Existing data to append to
     * @param  int  $page  Current page number for pagination
     * @return array Processed activity data
     *
     * @throws ConnectionException
     */
    private function findActivities(int $startOfDay, int $endOfDay, array $data, int $page = 1): array
    {
        $params = [
            'after' => $startOfDay,
            'before' => $endOfDay,
            'per_page' => 30,
            'page' => $page,
        ];

        $response = Http::withToken($this->accessToken)->get($this->apiUrl.'athlete/activities?'.http_build_query($params));

        if ($response->successful()) {
            $activities = collect($response->json());

            if ($activities->count()) {
                $page++;

                $data = array_merge($data, $activities->toArray());

                return $this->findActivities($startOfDay, $endOfDay, $data, $page);
            }
        }

        $activities = collect($data)->map(function ($activity) {
            if (! isset($activity['start_date'])) {
                return $activity;
            }

            $date = Carbon::parse($activity['start_date'])->format('Y-m-d');
            $distance = round(($activity['distance'] / 1609.344), 3);
            $modality = $this->modality($activity['sport_type']);

            return compact('date', 'distance', 'modality');
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

    /**
     * Fetch a specific activity by ID
     * Used by the webhook processor to get activity details
     *
     * @param  int  $activityId  The Strava activity ID
     * @return array The activity data
     *
     * @throws Exception If the activity cannot be fetched
     */
    private function fetchActivity(int $activityId): array
    {
        $response = Http::withToken($this->accessToken)
            ->get($this->apiUrl.'activities/'.$activityId);

        if (! $response->successful()) {
            throw new Exception('Failed to fetch activity: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Process activity data and prepare it for storage
     * Converts Strava-specific data to standardized format
     *
     * @param  array  $activity  The activity data from Strava
     * @param  DataSourceProfile  $sourceProfile  The user's data source profile
     * @return array Processed activity data
     */
    private function processActivity(array $activity, DataSourceProfile $sourceProfile): array
    {
        $date = Carbon::parse($activity['start_date_local'])->format('Y-m-d');
        $distanceInMiles = round(($activity['distance'] / 1609.344), 3);

        // Use the modality function to determine the activity type
        $modality = $this->modality($activity['type']);

        return [
            'user_id' => $sourceProfile->user_id,
            'date' => $date,
            'modality' => $modality,
            'distance' => $distanceInMiles,
            'transaction_id' => $activity['id'],
            'source' => 'strava',
        ];
    }
}
