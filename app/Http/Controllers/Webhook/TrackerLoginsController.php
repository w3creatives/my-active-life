<?php
/**
 * @Deprecated
 * I will be removed once changes are migrated
 */
namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataSourceProfile;
use App\Models\UserProfilePoint;
use Carbon\Carbon;
use App\Services\StravaService;
use App\Services\EventService;
use App\Services\MailService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TrackerLoginsController extends Controller
{
    private string $apiUrl;
    private string $clientId;
    private string $redirectUrl;
    private string $clientSecret;
    private string $authTokenUrl = 'https://www.strava.com/oauth/token';

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

    public function redirectToAuthUrl(StravaService $stravaService)
    {
        $authUrl = $stravaService->authUrl('app');

        return redirect($authUrl);
    }

    public function index(Request $request, StravaService $stravaService)
    {

        if ($request->get('action') == 'logout') {
            $request->session()->invalidate();
            return redirect()->route('tracker.login');
        }

        $authUrl = $stravaService->authUrl();
        $user = $request->session()->has('tracker_user');

        if ($user) {
            return redirect()->route('tracker.user.activities');
        }
        return view('tracker-login', compact('authUrl', 'user'));
    }

    public function stravaCallback(Request $request, StravaService $stravaService)
    {
        if ($request->get('debug') == true) {

            /*
                0 => "run"
                1 => "walk"
                2 => "bike"
                3 => "swim"
                4 => "other"
                5 => "daily_steps"
            */
            $data = $stravaService->setAccessToken("f2de4c3e7f96694c210a042a3417a7572d3ba581")->activities(request()->get('date'));

            $activities = collect($data);

            return $activities;
        }

        $response = $stravaService->authorize($request->get('code'));

        // $this->createSubscription();

        // dd($response, $request->get('state'));
        if ($request->get('state') == 'app') {
            if ($response == false) {
                //error=access_denied
                /*
                {#467 ▼ // app/Http/Controllers/TrackerLoginsController.php:44
                    +"token_type": "Bearer"
                    +"expires_at": 1738194171
                    +"expires_in": 21577
                    +"refresh_token": "d07d83e530a0b283bf96489ade8a9bee977c8c23"
                    +"access_token": "a1b67d68c3fd5712769c802ab4a54a7ea977b7f8"
                    +"athlete": {#471 ▼
                        +"id": 16005041
                        +"username": null
                        +"resource_state": 2
                        +"firstname": "Scott"
                        +"lastname": "Putnam"
                        +"bio": null
                        +"city": null
                        +"state": null
                        +"country": null
                        +"sex": "M"
                        +"premium": false
                        +"summit": false
                        +"created_at": "2016-06-26T19:46:12Z"
                        +"updated_at": "2025-01-29T17:24:24Z"
                        +"badge_type_id": 0
                        +"weight": 91.6257
                        +"profile_medium": "https://graph.facebook.com/10210176406546183/picture?height=256&width=256"
                        +"profile": "https://graph.facebook.com/10210176406546183/picture?height=256&width=256"
                        +"friend": null
                        +"follower": null
                    }
                    }
                */
                // dd($response);
                return response()->json(['message' => 'Unabled to complete your request'], 403);
            }

            return redirect(sprintf("rte://settings/%s/%s/%s/%s/%s/1", $response->access_token, $response->refresh_token, $response->expires_in, $response->athlete->id, 'strava'));

            return response()->json(['message' => 'Authentication completed']);
        }

        if ($response == false) {
            $request->session()->flash('status', ['type' => 'danger', 'message' => 'Unabled to complete your request']);

            return redirect()->route('tracker.login');
        }

        $request->session()->put('tracker_user', $response);
        $request->session()->flash('status', ['type' => 'success', 'message' => 'Welcome back, you can track your activities now']);

        return redirect()->route('tracker.user.activities');
    }

    public function userActivities(Request $request, StravaService $stravaService)
    {

        $user = $request->session()->get('tracker_user');

        if (!$user) {
            return redirect()->route('tracker.login');
        }

        $date = Carbon::parse($request->get('date', Carbon::now()));

        $data = $stravaService->setAccessToken($user->access_token)->activities(request()->get('date'));

        $activities = collect($data);

        return view('tracker-login', compact('user', 'activities', 'date'));
    }

    // Webhook process function
    public function handleWebhook(Request $request, EventService $eventService)
    {
        // Handle webhook verification challenge
        if ($request->isMethod('get') && $request->has('hub_challenge')) {
            Log::debug('TrackerWebhooksController: Handling Strava webhook verification challenge', [
                'hub_mode' => $request->get('hub_mode'),
                'hub_verify_token' => $request->get('hub_verify_token'),
                'hub_challenge' => $request->get('hub_challenge'),
            ]);

            if ($request->get('hub_verify_token') === config('services.strava.webhook_verification_code')) {
                return response()->json(['hub.challenge' => $request->get('hub_challenge')]);
            } else {
                Log::error('TrackerWebhooksController: Invalid Strava webhook verification token');

                return response('Invalid verification token', 403);
            }
        }

        // Process webhook event (for POST requests)
        Log::debug('TrackerWebhooksController: Processing Strava webhook event', [
            'payload' => $request->all(),
        ]);

        try {
            // Process the Strava webhook event
            $result = $this->processWebhook($request->all());
            Log::debug('TrackerWebhooksController: Strava webhook processed', ['result' => $result]);

            // If we have activity data and a user, create/update profile points
            if (isset($result['activity']) && isset($result['user']) && isset($result['sourceProfile'])) {
                $activity = $result['activity'];
                $user = $result['user'];
                $sourceProfile = $result['sourceProfile'];

                // Create user participation points
                $activity['dataSourceId'] = $sourceProfile->data_source_id;
                //$eventService->createUserParticipationPoints($user, $activity);

                // Create or update user profile point
                if (isset($activity['distance']) && isset($activity['date'])) {
                    $distance = $activity['distance']; // round(($activity['distance'] / 1609.344), 3);
                    $this->createPoints($eventService, $user, $activity['date'], $distance, $sourceProfile, null, $activity['transaction_id'], $activity['modality']);

                    $this->createOrUpdateUserProfilePoint(
                        $user,
                        $distance,
                        $activity['date'],
                        $sourceProfile,
                    );

                    Log::debug('TrackerWebhooksController: Updated user profile points for Strava activity', [
                        'user_id' => $user->id,
                        'distance' => $distance,
                        'date' => $activity['date'],
                    ]);
                }
            }

            // Always return 200 OK to Strava, even if we couldn't process the event
            // This prevents Strava from retrying the webhook
            return response()->json(['status' => 'success'], 200);
        } catch (Exception $e) {
            Log::error('TrackerWebhooksController: Error processing Strava webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Still return 200 OK to Strava to prevent retries
            return response()->json(['status' => 'error_handled'], 200);
        }
    }

    private function processWebhook(array $data)
    {
        Log::debug('StravaService: Processing webhook', ['data' => $data]);

        // Verify this is an activity creation event
        if (! isset($data['object_type']) || $data['object_type'] !== 'activity' || ! isset($data['aspect_type']) || $data['aspect_type'] !== 'create')
        {
            return ['status' => 'ignored', 'reason' => 'Not an activity creation event'];
        }

        // Find the user by owner_id (stored in access_token_secret in Ruby implementation)
        $sourceProfile = DataSourceProfile::where('access_token_secret', $data['owner_id'])
            ->whereHas('source', function ($query) {
                return $query->where('short_name', 'strava');
            })
            ->first();

        if (! $sourceProfile)
        {
            Log::error('StravaService: Unable to find user for owner_id', [
                'owner_id' => $data['owner_id'],
            ]);

            return ['status' => 'error', 'reason' => 'User not found'];
        }

        // Refresh token if needed
        if (Carbon::parse($sourceProfile->token_expires_at)->lt(Carbon::now()))
        {
            $this->refreshToken($sourceProfile->refresh_token, $sourceProfile);
            $sourceProfile->refresh();
        }

        // Set access token for API calls
        $this->accessToken = $sourceProfile->access_token;

        // Fetch the activity data
        try {
            $activity = $this->fetchActivity($data['object_id']);

            Log::debug("Process Webhook : Fetch Activities : ", $activity);

            // Process and store the activity data
            $processedActivity = $this->processActivity($activity, $sourceProfile);

            // Get the user from the source profile
            $user = $sourceProfile->user;

            // Return all the data needed for creating profile points
            return [
                'status' => 'success',
                'activity' => $processedActivity,
                'user' => $user,
                'sourceProfile' => $sourceProfile,
            ];
        } catch (Exception $e) {
            Log::error('StravaService: Failed to fetch activity', [
                'message' => $e->getMessage(),
                'activity_id' => $data['object_id'],
            ]);

            $this->refreshToken($sourceProfile->refresh_token, $sourceProfile);
            $sourceProfile->refresh();

            return ['status' => 'error', 'reason' => $e->getMessage()];
        }

        return ['status' => 'error', 'reason' => 'Unexpected error during webhook processing.'];
    }

    private function refreshToken(?string $refreshToken, $sourceProfile)
    {
        Log::debug("Refresh Token: {$refreshToken}", [
            'sourceProfile' => $sourceProfile->toArray()
        ]);

        if (! $refreshToken)
        {
            return ['error' => 'No refresh token provided'];
        }

        Log::debug('Sending refresh token request to: ' . $this->authTokenUrl, [
            'client_id' => $this->clientId,
            'grant_type' => 'refresh_token',
        ]);

        $response = Http::post($this->authTokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful())
        {
            $data = $response->json();
            Log::debug('Token refresh successful. Response data:', $data);

            $authResponse = [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_expires_at' => Carbon::createFromTimestamp($data['expires_at'])->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now(),
            ];

            // Direct update instead of fill+save to ensure database is updated
            DataSourceProfile::where('id', $sourceProfile->id)->update($authResponse);

            // $temp = $sourceProfile->fill($authResponse)->save();
            // Log::debug("Query: ", [
            //     'data' => $temp
            // ]);

            $updatedSourceProfile = DataSourceProfile::find($sourceProfile->id);

            if ($updatedSourceProfile) {
                Log::debug('Verified data from database:', $updatedSourceProfile->toArray());
            } else {
                Log::warning('Cannot verify database update: Source profile ID not found after refresh.');
            }

            return ['status' => 'success'];
        }

        Log::error('refreshToken: Failed to refresh token.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return ['error' => $response->body()];
    }

    private function fetchActivity(int $activityId): array
    {
        // Ensure accessToken is set before making the request
        if (empty($this->accessToken)) {
            Log::error('fetchActivity: No access token available for fetching activity ' . $activityId);
            throw new Exception('No access token available.');
        }

        $response = Http::withToken($this->accessToken)
            ->get($this->apiUrl.'activities/'.$activityId);

        if (! $response->successful()) {
            throw new Exception('Failed to fetch activity: '.$response->body());
        }

        return $response->json();
    }

    private function processActivity(array $activity, DataSourceProfile $sourceProfile): array
    {
        $date = Carbon::parse($activity['start_date_local'])->format('Y-m-d');
        $distanceInMiles = round(($activity['distance'] / 1609.344), 3);

        // Use the modality function to determine the activity type
        $modality = $this->modality($activity['type']);

        Log::debug("Modality : {$modality}");

        return [
            'user_id' => $sourceProfile->user_id,
            'date' => $date,
            'modality' => $modality,
            'distance' => $distanceInMiles,
            'raw_distance' => $distanceInMiles, // Add raw_distance for profile points
            'transaction_id' => $activity['id'],
            'source' => 'strava',
            'data_source_id' => $sourceProfile->data_source_id,
        ];
    }

    private function modality(string $modality): string
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

    private function createOrUpdateUserProfilePoint($user, $distance, $date, $sourceProfile, $type='webhook',$actionType="auto")
    {
        $profilePoint = $user->profilePoints()->where('date', $date)->where('data_source_id', $sourceProfile->data_source_id)->first();

        $data = [
            "{$type}_distance_km" => $distance,
            "{$type}_distance_mile" => ($distance * 0.621371),
            'date' => $date,
            'data_source_id' => $sourceProfile->data_source_id,
            'action_type' => $actionType
        ];

        if ($profilePoint) {
            $profilePoint->fill($data)->save();
        } else {
            $user->profilePoints()->create($data);
        }
    }

    private function createSubscription(): array
    {
        $response = Http::post('https://www.strava.com/api/v3/push_subscriptions', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'callback_url' => route('webhook.strava'),
            'verify_token' => config('services.strava.webhook_verification_code'),
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
            'callback_url' => route('webhook.strava'),
        ]);

        return ['error' => $response->body()];
    }

    private function createPoints($eventService, $user, $date, $distance, $sourceProfile, $eventId=null, $transcationId = null, $modality = "other")
    {
        if(!$distance){
            return false;
        }


        if($eventId) {
            $participations = $user->participations()->where('event_id', $eventId)
                ->where('subscription_end_date', '>=', $date)->where('subscription_start_date', '<=', $date)
                ->get();

        } else {
            $participations = $user->participations()
                ->where('subscription_end_date', '>=', $date)->where('subscription_start_date', '<=', $date)->get();
        }

        if(!$participations->count()) {
            return false;
        }

        foreach($participations as $participation)
        {
            $pointdata = [
                'amount' => $distance,
                'date' => $date,
                'event_id' => $participation->event_id,
                'modality' => $modality,
                'data_source_id' => $sourceProfile->data_source_id,
                'transaction_id' => $transcationId
            ];

            Log::debug("createPoints : foreach : ", $pointdata);

            $userPoint = $user->points()->where([
                'date' => $date,
                'modality' => $modality,
                'event_id' => $participation->event_id,
                'data_source_id' => $sourceProfile->data_source_id,
                'transaction_id' => $transcationId
            ])->first();

            if($userPoint) {
                $userPoint->update($pointdata);
            } else{
                $user->points()->create($pointdata);
            }

            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $date);
            $eventService->userPointWorkflow($user->id, $participation->event_id);

            (new MailService)->sendCelebrationMail($participation->event_id, $user->id);
        }

        return true;
    }
}
