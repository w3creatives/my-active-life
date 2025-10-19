<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\DataSourceInterface;
use App\Models\DataSourceProfile;
use App\Traits\CalculateDaysTrait;
use App\Traits\DeviceLoggerTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for interacting with the Oura Ring API
 * Handles authentication, data retrieval, and webhook processing
 */
final class OuraRingService implements DataSourceInterface
{
    use CalculateDaysTrait;
    use DeviceLoggerTrait;

    private string $apiUrl = 'https://api.ouraring.com/v2/usercollection/';

    private string $accessToken;

    private string $clientId;

    private string $redirectUrl;

    private string $clientSecret;

    private string $authUrl = 'https://cloud.ouraring.com/oauth/authorize';

    private string $authTokenUrl = 'https://api.ouraring.com/oauth/token';

    private string $deregisterUrl = 'https://api.ouraring.com/oauth/revoke';

    private string $ouraWebhookVerificationCode;

    private array $authResponse;

    private CarbonImmutable $startDate;

    private CarbonImmutable $endDate;

    private float $dateDays;

    /**
     * Initialize the Oura Ring service with configuration values
     *
     * @param  string  $accessToken  Optional access token for authenticated requests
     */
    public function __construct(string $accessToken = '')
    {
        $this->accessToken = $accessToken;

        $this->clientId = config('services.ouraring.client_id');
        $this->redirectUrl = config('services.ouraring.redirect_url');
        $this->clientSecret = config('services.ouraring.client_secret');
        $this->ouraWebhookVerificationCode = config('services.ouraring.webhook_verification_code');
    }

    public function setSecrets($secrets): self
    {
        if (is_array($secrets)) {
            [$accessToken] = $secrets;
        } else {
            $accessToken = $secrets;
        }

        $this->setAccessToken($accessToken);

        return $this;
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
     * Set the access token secret (not used in Oura Ring OAuth 2.0)
     * Implemented for interface compatibility
     *
     * @param  string  $accessTokenSecret  The token secret
     */
    public function setAccessTokenSecret(string $accessTokenSecret): self
    {
        return $this;
    }

    /**
     * Generate the OAuth authorization URL for Oura Ring
     * Used to redirect users to Oura Ring for authentication
     *
     * @return string The complete authorization URL
     */
    public function authUrl(): string
    {
        return $this->authUrl.'?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'scope' => 'daily',
        ]);
    }

    /**
     * Exchange authorization code for access token
     * Called after user authorizes the application on Oura Ring
     *
     * @param  array  $config  Array containing the authorization code
     */
    public function authorize(array $config): self
    {
        [$code] = $config;

        $response = Http::asForm()->post($this->authTokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            $data = $response->object();

            $tokenExpiresAt = isset($data->expires_in)
                ? now()->addSeconds($data->expires_in)
                : null;

            // Fetch user info to get user_id
            $userId = $this->fetchUserId($data->access_token);

            $this->authResponse = [
                'access_token' => $data->access_token,
                'refresh_token' => $data->refresh_token ?? null,
                'token_expires_at' => $tokenExpiresAt,
                'user_id' => $userId,
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
     * Oura Ring tokens expire and must be refreshed
     *
     * @param  string|null  $refreshToken  The refresh token from previous authorization
     * @return array The new token information or empty array on failure
     */
    public function refreshToken(?string $refreshToken): array
    {
        if (! $refreshToken) {
            return [];
        }

        $response = Http::asForm()->post($this->authTokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $data = json_decode($response->body(), true);

        if (isset($data['access_token'])) {
            $profileData = collect($data)->only(['access_token', 'refresh_token'])->toArray();
            $profileData['token_expires_at'] = Carbon::now()->addSeconds($data['expires_in'])->format('Y-m-d H:i:s');

            return $profileData;
        }

        return [];
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
        if (! is_null($startDate)) {
            [$startDate, $endDate, $dateDays] = $this->daysFromStartEndDate($startDate, $endDate);

            $this->startDate = $startDate;

            $this->endDate = $endDate;

            $this->dateDays = $dateDays;
        }

        return $this;
    }

    /**
     * Retrieve activities for the configured date range
     * Fetches daily activity summaries and workouts from Oura Ring
     *
     * @param  string  $responseType
     * @return Collection Collection of processed activity data
     */
    public function activities($responseType = 'data'): Collection
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

    /**
     * Verify webhook request from Oura Ring
     * Compares the verification code with the configured value
     *
     * @param  string  $code  The verification code from Oura Ring
     * @return bool True if verification is successful
     */
    public function verifyWebhook(string $code): bool
    {
        return $code === $this->ouraWebhookVerificationCode;
    }

    /**
     * Process webhook event from Oura Ring
     * Fetches and processes activity data when webhook is triggered
     *
     * @param  array  $data  The webhook payload
     * @return array Processed activity data
     */
    public function processWebhook(array $data): array
    {
        Log::debug('OuraRingService: Processing webhook', ['data' => $data]);

        // Verify this is a valid webhook event
        if (! isset($data['event_type']) || ! isset($data['user_id'])) {
            return ['status' => 'ignored', 'reason' => 'Invalid webhook data'];
        }

        // Find the user by user_id
        $sourceProfile = DataSourceProfile::where('access_token_secret', $data['user_id'])
            ->orWhere('user_id', $data['user_id'])
            ->whereHas('source', function ($query) {
                return $query->where('short_name', 'ouraring');
            })
            ->first();

        if (! $sourceProfile) {
            Log::error('OuraRingService: Unable to find user for user_id', [
                'user_id' => $data['user_id'],
            ]);

            return ['status' => 'error', 'reason' => 'User not found'];
        }

        // Refresh token if needed
        if ($sourceProfile->token_expires_at && Carbon::parse($sourceProfile->token_expires_at)->lt(Carbon::now())) {
            $newTokens = $this->refreshToken($sourceProfile->refresh_token);
            if (! empty($newTokens)) {
                $sourceProfile->update($newTokens);
                $sourceProfile->refresh();
            }
        }

        // Set access token for API calls
        $this->accessToken = $sourceProfile->access_token;

        // Get the user from the source profile
        $user = $sourceProfile->user;

        // Return all the data needed for creating profile points
        return [
            'status' => 'success',
            'user' => $user,
            'sourceProfile' => $sourceProfile,
            'date' => $data['date'] ?? now()->format('Y-m-d'),
        ];
    }

    /**
     * Format webhook request from Oura Ring
     * Processes webhook notifications and retrieves user information
     *
     * @param  mixed  $request  The webhook request
     * @return Collection Collection of formatted webhook data
     */
    public function formatWebhookRequest($request): Collection
    {
        $notifications = collect($request->_json ? collect($request->_json) : $request->all());

        $items = $notifications->map(function ($notification) {
            $userId = $notification['user_id'] ?? null;

            if (! $userId) {
                return null;
            }

            $sourceProfile = DataSourceProfile::where('access_token_secret', $userId)
                ->orWhere('user_id', $userId)
                ->whereHas('source', function ($query) {
                    return $query->where('short_name', 'ouraring');
                })
                ->first();

            $user = $sourceProfile?->user;

            return (object) [
                'user' => $user,
                'date' => $notification['date'] ?? now()->format('Y-m-d'),
                'sourceProfile' => $sourceProfile,
                'dataSourceId' => $sourceProfile ? $sourceProfile->data_source_id : null,
                'sourceToken' => $sourceProfile ? $sourceProfile->access_token : null,
                'webhookUrl' => null,
                'extra' => array_merge($notification, ['userId' => $userId, 'source' => 'ouraring']),
            ];
        });

        return $items->filter(function ($item) {
            return $item && $item->user && $item->sourceProfile && $item->sourceToken;
        });
    }

    /**
     * Subscribe a user to Oura Ring webhook notifications
     * Note: Oura Ring API v2 uses data notification subscriptions
     *
     * @param  int  $userId  The user ID
     * @param  string  $subscriptionId  The subscription ID
     * @return array Response from subscription request
     */
    public function subscribe(int $userId, string $subscriptionId): array
    {
        // Oura Ring v2 API requires subscription setup through the developer portal
        // No direct API endpoint for subscription management
        Log::info('OuraRingService: Subscription request', [
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
        ]);

        return ['status' => 'success', 'message' => 'Oura Ring subscriptions are managed through the developer portal'];
    }

    /**
     * Deregister/revoke access for a user
     * Revokes the access token
     *
     * @param  mixed  $profile  The data source profile
     * @return bool Success status of deregistration
     */
    public function deregister($profile): bool
    {
        $accessToken = $profile->access_token;

        $response = Http::asForm()->post($this->deregisterUrl, [
            'token' => $accessToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $this->logger($profile, 'Oura Ring Revoked', $response);

        return $response->successful();
    }

    /**
     * Find activities for a specific date
     * Retrieves daily activity summaries and workouts
     *
     * @param  string  $date  The date in Y-m-d format
     * @return array Processed activity data
     */
    private function findActivities(string $date): array
    {
        // Fetch daily activity data
        $activityResponse = Http::withToken($this->accessToken)
            ->get($this->apiUrl.'daily_activity', [
                'start_date' => $date,
                'end_date' => $date,
            ]);

        // Fetch workout data
        $workoutResponse = Http::withToken($this->accessToken)
            ->get($this->apiUrl.'workout', [
                'start_date' => $date,
                'end_date' => $date,
            ]);

        $activities = collect([]);

        if ($activityResponse->successful()) {
            $dailyData = collect($activityResponse->json('data', []));

            foreach ($dailyData as $day) {
                // Extract steps and calculate distance
                $steps = $day['steps'] ?? 0;
                $activeCalories = $day['active_calories'] ?? 0;

                // Oura provides meters_to_target and other metrics
                // We'll use a simple step-to-mile conversion: ~2000 steps = 1 mile
                if ($steps > 0) {
                    $distance = round($steps / 2000, 3);

                    $activities->push([
                        'date' => $day['day'] ?? $date,
                        'distance' => $distance,
                        'modality' => 'daily_steps',
                        'raw_distance' => $steps,
                    ]);
                }
            }
        }

        if ($workoutResponse->successful()) {
            $workouts = collect($workoutResponse->json('data', []));

            foreach ($workouts as $workout) {
                $distance = isset($workout['distance']) ? round($workout['distance'] / 1609.344, 3) : 0;
                $modality = $this->modality($workout['activity'] ?? 'other');

                if ($distance > 0) {
                    $activities->push([
                        'date' => $workout['day'] ?? $date,
                        'distance' => $distance,
                        'modality' => $modality,
                        'raw_distance' => $workout['distance'] ?? 0,
                    ]);
                }
            }
        }

        // Aggregate activities by modality
        $items = $activities->reduce(function ($data, $item) {
            if (! isset($data[$item['modality']])) {
                $data[$item['modality']] = $item;

                return $data;
            }

            $data[$item['modality']]['distance'] += $item['distance'];
            $data[$item['modality']]['raw_distance'] += $item['raw_distance'];

            return $data;
        }, []);

        return collect($items)->values()->toArray();
    }

    /**
     * Fetch the Oura Ring user ID using an access token
     * Required since Oura doesn't return user_id in token response
     *
     * @param  string  $accessToken  The access token
     * @return string|null The user ID or null on failure
     */
    private function fetchUserId(string $accessToken): ?string
    {
        try {
            $response = Http::withToken($accessToken)
                ->get('https://api.ouraring.com/v2/usercollection/personal_info');

            if ($response->successful()) {
                $data = $response->json();

                return $data['id'] ?? null;
            }

            Log::warning('OuraRingService: Failed to fetch user ID', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('OuraRingService: Exception fetching user ID', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Map Oura Ring activity types to standardized modality categories
     * Consistent with other services like Fitbit, Garmin, and Strava
     *
     * @param  string  $modality  The Oura Ring activity type
     * @return string The standardized modality category
     */
    private function modality(string $modality): string
    {
        return match (mb_strtolower($modality)) {
            'running', 'run', 'treadmill', 'trail running' => 'run',
            'walking', 'walk', 'hiking', 'hike' => 'walk',
            'cycling', 'bike', 'biking', 'spinning', 'indoor cycling' => 'bike',
            'swimming', 'swim', 'open water swimming' => 'swim',
            default => 'other',
        };
    }
}
