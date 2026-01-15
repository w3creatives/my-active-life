<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\DataSourceInterface;
use App\Traits\CalculateDaysTrait;
use App\Traits\DeviceLoggerTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service for interacting with the Oura Ring API
 * Handles authentication and data retrieval for daily activities and workouts
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

    private $dataSourceProfile = null;

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
     * Set the data source profile for token refresh
     * Allows automatic token refresh when needed
     *
     * @param  mixed  $profile  The DataSourceProfile model
     */
    public function setProfile($profile): self
    {
        $this->dataSourceProfile = $profile;

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
            'scope' => 'daily workout',
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
        // Refresh token if expired and profile is available
        $this->refreshTokenIfNeeded();

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
     * Verify webhook subscription from Oura Ring
     * Oura Ring sends verification_token and challenge during webhook setup
     *
     * @param  array|string  $data  Verification data (can be array for GET params or string for legacy)
     * @return array|bool Array with challenge if valid, false otherwise
     */
    public function verifyWebhook(array|string $data): array|bool
    {
        // Handle array input (GET parameters from Oura Ring verification)
        if (is_array($data)) {
            $verificationToken = $data['verification_token'] ?? null;
            $challenge = $data['challenge'] ?? null;

            Log::debug('OuraRingService: Webhook verification attempt', [
                'has_verification_token' => ! empty($verificationToken),
                'has_challenge' => ! empty($challenge),
                'token_matches' => $verificationToken === $this->ouraWebhookVerificationCode,
            ]);

            // Validate required parameters
            if (! $verificationToken || ! $challenge) {
                Log::warning('OuraRingService: Missing verification parameters', [
                    'missing_token' => empty($verificationToken),
                    'missing_challenge' => empty($challenge),
                ]);

                return false;
            }

            // Verify the token matches what we expect
            if ($verificationToken === $this->ouraWebhookVerificationCode) {
                Log::info('OuraRingService: Webhook verification successful', [
                    'challenge' => $challenge,
                ]);

                return ['challenge' => $challenge];
            }

            Log::warning('OuraRingService: Webhook verification failed - invalid token', [
                'received_token_length' => mb_strlen($verificationToken),
                'expected_token_length' => mb_strlen($this->ouraWebhookVerificationCode ?? ''),
            ]);

            return false;
        }

        // Handle string input for backwards compatibility
        Log::debug('OuraRingService: String verification attempt');

        return $data === $this->ouraWebhookVerificationCode;
    }

    /**
     * Verify webhook signature from Oura Ring POST request
     * Validates HMAC signature for security
     *
     * @param  string  $signature  The X-Oura-Signature header value
     * @param  string  $timestamp  The X-Oura-Timestamp header value
     * @param  string  $payload  The raw request body
     * @return bool True if signature is valid, false otherwise
     */
    public function verifyWebhookSignature(string $signature, string $timestamp, string $payload): bool
    {
        // Create HMAC signature using timestamp and payload
        $calculatedSignature = hash_hmac('sha256', $timestamp.$payload, $this->clientSecret);

        // Convert to uppercase for comparison (as per Oura documentation)
        $calculatedSignature = mb_strtoupper($calculatedSignature);

        // Use hash_equals for timing-attack-safe comparison
        return hash_equals($calculatedSignature, mb_strtoupper($signature));
    }

    /**
     * Subscribe a user to Oura Ring webhook notifications
     * Creates a webhook subscription using the v2/webhook/subscription endpoint
     *
     * @param  int  $userId  The user ID
     * @param  string  $subscriptionId  The subscription ID (user_id from Oura)
     * @return array Subscription status information
     */
    public function subscribe(int $userId, string $subscriptionId): array
    {
        // Generate the callback URL using the route name
        $callbackUrl = route('profile.device-sync.webhook', ['sourceSlug' => 'ouraring']);

        $dataTypes = [
            'workout',
            'daily_activity',
            'enhanced_tag',
            'tag',
        ];

        // Construct webhook subscription URL
        $webhookUrl = 'https://api.ouraring.com/v2/webhook/subscription';
        $results = [];

        foreach ($dataTypes as $dataType) {
            try {
                $response = Http::withToken($this->accessToken)
                    ->withHeaders([
                        'x-client-id' => $this->clientId,
                        'x-client-secret' => $this->clientSecret,
                    ])
                    ->post($webhookUrl, [
                        'callback_url' => $callbackUrl,
                        'verification_token' => $this->ouraWebhookVerificationCode,
                        'event_type' => 'create',
                        'data_type' => $dataType,
                    ]);

                if ($response->status() === 201) {
                    Log::info('OuraRingService: Webhook subscription created', [
                        'user_id' => $userId,
                        'subscription_id' => $subscriptionId,
                        'data_type' => $dataType,
                    ]);

                    $results[$dataType] = [
                        'status' => 'created',
                        'response' => $response->json(),
                    ];
                } elseif ($response->status() === 422) {
                    // Subscription already exists
                    Log::info('OuraRingService: Webhook subscription already exists', [
                        'user_id' => $userId,
                        'subscription_id' => $subscriptionId,
                        'data_type' => $dataType,
                    ]);

                    $results[$dataType] = [
                        'status' => 'already_exists',
                    ];
                } else {
                    Log::error('OuraRingService: Webhook subscription failed', [
                        'user_id' => $userId,
                        'subscription_id' => $subscriptionId,
                        'data_type' => $dataType,
                        'status_code' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    $results[$dataType] = [
                        'status' => 'error',
                        'status_code' => $response->status(),
                        'response' => $response->json(),
                    ];
                }
            } catch (Throwable $e) {
                Log::error('OuraRingService: Exception while creating webhook subscription', [
                    'user_id' => $userId,
                    'data_type' => $dataType,
                    'message' => $e->getMessage(),
                ]);

                $results[$dataType] = [
                    'status' => 'exception',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Fetch webhook data from Oura Ring API
     * Retrieves the actual activity/workout data using the object_id from webhook
     *
     * @param  string  $dataType  The data type (workout, daily_activity, etc.)
     * @param  string  $objectId  The object ID from webhook
     * @return array|null The fetched activity data or null on failure
     */
    public function fetchWebhookData(string $dataType, string $objectId): ?array
    {
        try {
            $url = $this->apiUrl.$dataType.'/'.$objectId;

            Log::debug('OuraRingService: Fetching webhook data', [
                'url' => $url,
                'data_type' => $dataType,
                'object_id' => $objectId,
            ]);

            $response = Http::withToken($this->accessToken)->get($url);

            if (! $response->successful()) {
                Log::error('OuraRingService: Failed to fetch webhook data', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'data_type' => $dataType,
                    'object_id' => $objectId,
                ]);

                return null;
            }

            $data = $response->json();

            // Transform the data to match the expected format
            return $this->transformWebhookData($dataType, $data);
        } catch (\Exception $e) {
            Log::error('OuraRingService: Exception fetching webhook data', [
                'message' => $e->getMessage(),
                'data_type' => $dataType,
                'object_id' => $objectId,
            ]);

            return null;
        }
    }

    /**
     * Process webhook data from Oura Ring
     * Handles incoming webhook notifications about new data
     *
     * @param  array  $data  The webhook payload
     * @return array Processed webhook data
     */
    public function processWebhook(array $data): array
    {
        Log::debug('OuraRingService: Processing webhook', ['data' => $data]);

        try {
            // Handle nested payload structure
            $payload = $data['payload'] ?? $data;

            $eventType = $payload['event_type'] ?? null;
            $dataType = $payload['data_type'] ?? null;
            $objectId = $payload['object_id'] ?? null;
            $eventTime = $payload['event_time'] ?? null;
            $userId = $payload['user_id'] ?? null;

            if (! $eventType || ! $dataType || ! $objectId || ! $userId) {
                return ['status' => 'ignored', 'reason' => 'Missing required webhook data'];
            }

            // Find the user by Oura Ring user_id (stored in access_token_secret field)
            $sourceProfile = \App\Models\DataSourceProfile::where('access_token_secret', $userId)
                ->whereHas('source', function ($query) {
                    return $query->where('short_name', 'ouraring');
                })
                ->first();

            if (! $sourceProfile) {
                Log::error('OuraRingService: Unable to find user for user_id', [
                    'user_id' => $userId,
                ]);

                return ['status' => 'error', 'reason' => 'User not found'];
            }

            // Set profile and refresh token if needed (uses refreshTokenIfNeeded internally)
            $this->dataSourceProfile = $sourceProfile;
            $this->accessToken = $sourceProfile->access_token;
            $this->refreshTokenIfNeeded();

            // Get the user from the source profile
            $user = $sourceProfile->user;

            // Return webhook processing result
            return [
                'status' => 'success',
                'event_type' => $eventType,
                'data_type' => $dataType,
                'object_id' => $objectId,
                'user' => $user,
                'sourceProfile' => $sourceProfile,
            ];
        } catch (\Exception $e) {
            Log::error('OuraRingService: Failed to process webhook', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    /**
     * Format webhook request for processing
     * Converts Oura Ring webhook data to standardized format
     *
     * @param  \Illuminate\Http\Request  $request  The webhook request
     * @return Collection Collection of formatted webhook items
     */
    public function formatWebhookRequest($request): Collection
    {
        // Oura Ring sends webhook data differently than Fitbit/Garmin
        // This method adapts the webhook data to the expected format
        $webhookData = $request->_json ? collect($request->_json) : collect($request->all());

        Log::debug('OuraRingService: Formatting webhook request', ['data' => $webhookData]);

        // Oura sends individual webhook events, not arrays
        $userId = $webhookData->get('user_id');

        if (! $userId) {
            return collect([]);
        }

        $sourceProfile = \App\Models\DataSourceProfile::where('access_token_secret', $userId)
            ->whereHas('source', function ($query) {
                return $query->where('short_name', 'ouraring');
            })
            ->first();

        if (! $sourceProfile) {
            Log::warning('OuraRingService: Source profile not found', ['user_id' => $userId]);

            return collect([]);
        }

        $user = $sourceProfile->user;

        // Format as a collection item
        $item = (object) [
            'user' => $user,
            'date' => null, // Will be determined when fetching activities
            'sourceProfile' => $sourceProfile,
            'dataSourceId' => $sourceProfile->data_source_id,
            'sourceToken' => $sourceProfile->access_token,
            'webhookUrl' => null, // Not needed for Oura Ring
            'extra' => [
                'event_type' => $webhookData->get('event_type'),
                'data_type' => $webhookData->get('data_type'),
                'object_id' => $webhookData->get('object_id'),
                'user_id' => $userId,
                'source' => 'ouraring',
            ],
        ];

        return collect([$item])->filter(function ($item) {
            return $item->user && $item->sourceProfile;
        });
    }

    /**
     * Transform webhook data to standardized activity format
     * Converts Oura Ring API response to the format expected by the system
     *
     * @param  string  $dataType  The data type (workout, daily_activity, etc.)
     * @param  array  $data  The raw data from Oura Ring API
     * @return array Transformed activity data
     */
    private function transformWebhookData(string $dataType, array $data): array
    {
        if ($dataType === 'workout') {
            // Distance is in meters, convert to miles (1 mile = 1609.344 meters)
            $distanceMeters = $data['distance'] ?? 0;
            $distance = $distanceMeters ? round($distanceMeters / 1609.344, 3) : 0;
            $modality = $this->modality($data['activity'] ?? 'other');

            return [
                'id' => $data['id'] ?? null,
                'date' => $data['day'] ?? now()->format('Y-m-d'),
                'steps' => 0,
                'distance' => $distance,
                'modality' => $modality,
                'raw_distance' => $distanceMeters,
                'calories' => $data['calories'] ?? 0,
                'transaction_id' => $data['id'] ?? null,
            ];
        }

        if ($dataType === 'daily_activity') {
            // For daily activity, steps are the primary data
            $steps = $data['steps'] ?? 0;
            $distance = $steps > 0 ? round($steps / 2000, 3) : 0;

            return [
                'id' => $data['id'] ?? null,
                'date' => $data['day'] ?? now()->format('Y-m-d'),
                'steps' => $steps,
                'distance' => $distance,
                'modality' => 'daily_steps',
                'raw_distance' => $steps,
                'calories' => $data['active_calories'] ?? 0,
                'transaction_id' => $data['id'] ?? null,
            ];
        }

        // For other data types (enhanced_tag, tag), return empty activity
        // These are not tracked as activities in the system
        return [];
    }

    /**
     * Find activities for a specific date
     * Retrieves daily activity summaries and workouts from Oura Ring API
     *
     * @param  string  $date  The date in Y-m-d format
     * @return array Processed activity data
     */
    private function findActivities(string $date): array
    {
        // Fetch daily activity data
        $activityResponse = Http::withToken($this->accessToken)
            ->get($this->apiUrl.'daily_activity', [
                'start_date' => $this->startDate->format('Y-m-d'),
                'end_date' => $this->endDate->format('Y-m-d'),
            ]);

        // Fetch workout data
        $workoutResponse = Http::withToken($this->accessToken)
            ->get($this->apiUrl.'workout', [
                'start_date' => $this->startDate->format('Y-m-d'),
                'end_date' => $this->endDate->format('Y-m-d'),
            ]);

        $activities = collect([]);

        // Process daily activity data (steps)
        if ($activityResponse->successful()) {
            $dailyData = collect($activityResponse->json('data', []));

            foreach ($dailyData as $day) {
                $steps = $day['steps'] ?? 0;

                // Convert steps to miles using standard conversion: ~2000 steps = 1 mile
                if ($steps > 0) {
                    $distance = round($steps / 2000, 3);

                    $activities->push([
                        'id' => $day['id'],
                        'date' => $day['day'] ?? $date,
                        'steps' => $steps,
                        'distance' => $distance,
                        'modality' => 'daily_steps',
                        'raw_distance' => $steps,
                        'calories' => $day['active_calories'] ?? 0,
                    ]);
                }
            }
        }

        // Process workout data
        if ($workoutResponse->successful()) {
            $workouts = collect($workoutResponse->json('data', []));

            foreach ($workouts as $workout) {
                // Distance is in meters, convert to miles (1 mile = 1609.344 meters)
                $distanceMeters = $workout['distance'] ?? null;
                $distance = $distanceMeters ? round($distanceMeters / 1609.344, 3) : 0;
                $modality = $this->modality($workout['activity'] ?? 'other');

                if ($distance > 0) {
                    $activities->push([
                        'id' => $workout['id'],
                        'date' => $workout['day'] ?? $date,
                        'steps' => $workout['steps'] ?? 0,
                        'distance' => $distance,
                        'modality' => $modality,
                        'raw_distance' => $distanceMeters,
                        'calories' => $workout['calories'] ?? 0,
                    ]);
                }
            }
        }

        // Aggregate activities by date and modality
        $items = $activities->reduce(function ($data, $item) {
            $key = $item['date'].'_'.$item['modality'];

            if (! isset($data[$key])) {
                $data[$key] = $item;
                // Initialize transaction_ids array with the first ID
                $data[$key]['transaction_ids'] = [$item['id']];

                return $data;
            }

            // Aggregate values
            $data[$key]['distance'] += $item['distance'];
            $data[$key]['raw_distance'] += $item['raw_distance'];
            $data[$key]['calories'] += $item['calories'];
            // Collect all transaction IDs
            $data[$key]['transaction_ids'][] = $item['id'];

            return $data;
        }, []);

        // Adjust daily_steps to avoid double-counting with specific activities
        // Build a map of dates to their composite keys (preserves original keys)
        $keysByDate = [];
        foreach ($items as $compositeKey => $activity) {
            $date = $activity['date'];
            if (! isset($keysByDate[$date])) {
                $keysByDate[$date] = [];
            }
            $keysByDate[$date][] = $compositeKey;
        }

        $adjustedItems = [];

        // Process each date using the original composite keys
        foreach ($keysByDate as $date => $compositeKeys) {
            $dailyStepsKey = null;
            $dailyStepsItem = null;
            $otherActivitiesDistance = 0;
            $otherActivitiesRawDistance = 0;

            // First pass: identify daily_steps and calculate other activities total
            foreach ($compositeKeys as $compositeKey) {
                $activity = $items[$compositeKey];

                if ($activity['modality'] === 'daily_steps') {
                    $dailyStepsKey = $compositeKey;
                    $dailyStepsItem = $activity;
                } else {
                    // Add other activities to result (keep them as-is)
                    $adjustedItems[$compositeKey] = $activity;
                    // Sum their distances for daily_steps adjustment
                    $otherActivitiesDistance += $activity['distance'];
                    $otherActivitiesRawDistance += $activity['raw_distance'];
                }
            }

            // Adjust daily_steps if both daily_steps and other activities exist
            if ($dailyStepsItem && $otherActivitiesDistance > 0) {
                $adjustedDistance = $dailyStepsItem['distance'] - $otherActivitiesDistance;
                $adjustedRawDistance = $dailyStepsItem['raw_distance'] - $otherActivitiesRawDistance;

                // Only include daily_steps if remaining distance is positive
                if ($adjustedDistance > 0) {
                    $dailyStepsItem['distance'] = round($adjustedDistance, 3);
                    $dailyStepsItem['raw_distance'] = round($adjustedRawDistance, 3);
                    // Convert transaction_ids array to comma-separated string
                    $dailyStepsItem['transaction_id'] = implode(',', $dailyStepsItem['transaction_ids'] ?? []);
                    unset($dailyStepsItem['transaction_ids']);
                    $adjustedItems[$dailyStepsKey] = $dailyStepsItem;
                }

                Log::debug('OuraRingService: Adjusted daily_steps to avoid double-counting', [
                    'date' => $date,
                    'original_daily_steps_distance' => round($dailyStepsItem['distance'] + $otherActivitiesDistance, 3),
                    'other_activities_distance' => round($otherActivitiesDistance, 3),
                    'adjusted_daily_steps_distance' => $adjustedDistance > 0 ? round($adjustedDistance, 3) : 0,
                ]);
            } elseif ($dailyStepsItem) {
                // No other activities, keep daily_steps as-is
                // Convert transaction_ids array to comma-separated string
                $dailyStepsItem['transaction_id'] = implode(',', $dailyStepsItem['transaction_ids'] ?? []);
                unset($dailyStepsItem['transaction_ids']);
                $adjustedItems[$dailyStepsKey] = $dailyStepsItem;
            }
        }

        // Convert transaction_ids to transaction_id for all other activities
        foreach ($adjustedItems as $key => $item) {
            if (isset($item['transaction_ids'])) {
                $adjustedItems[$key]['transaction_id'] = implode(',', $item['transaction_ids']);
                unset($adjustedItems[$key]['transaction_ids']);
            }
        }

        return collect($adjustedItems)->values()->toArray();
    }

    /**
     * Refresh access token if needed
     * Checks if token is expired and refreshes it automatically
     */
    private function refreshTokenIfNeeded(): void
    {
        // Only refresh if we have a profile with token expiration info
        if (! $this->dataSourceProfile) {
            return;
        }

        // Check if token is expired
        if ($this->dataSourceProfile->token_expires_at &&
            Carbon::parse($this->dataSourceProfile->token_expires_at)->lt(Carbon::now())) {

            Log::info('OuraRingService: Token expired, refreshing...', [
                'profile_id' => $this->dataSourceProfile->id,
                'expires_at' => $this->dataSourceProfile->token_expires_at,
            ]);

            $newTokens = $this->refreshToken($this->dataSourceProfile->refresh_token);

            if (! empty($newTokens)) {
                // Update the profile with new tokens
                $this->dataSourceProfile->update($newTokens);
                $this->dataSourceProfile->refresh();

                // Update the current access token
                $this->accessToken = $this->dataSourceProfile->access_token;

                Log::info('OuraRingService: Token refreshed successfully', [
                    'profile_id' => $this->dataSourceProfile->id,
                    'new_expires_at' => $this->dataSourceProfile->token_expires_at,
                ]);
            } else {
                Log::error('OuraRingService: Failed to refresh token', [
                    'profile_id' => $this->dataSourceProfile->id,
                ]);
            }
        }
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
