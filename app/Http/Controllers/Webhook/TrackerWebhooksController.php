<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Interfaces\DataSourceInterface;
use App\Repositories\SopifyRepository;
use App\Services\EventService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function Pest\Laravel\json;

final class TrackerWebhooksController extends Controller
{
    private $tracker;

    public function __construct()
    {
        $this->tracker = app(DataSourceInterface::class);
    }

    public function verifyWebhook(Request $request, string $sourceSlug = 'fitbit')
    {
        Log::debug('TrackerWebhooksController: Verifying webhook', [
            'sourceSlug' => $sourceSlug,
            'params' => $request->all(),
        ]);

        if ($sourceSlug === 'strava') {
            // Handle Strava webhook verification
            $response = $this->tracker->get($sourceSlug)->verifyWebhook($request->all());

            if ($response) {
                return response()->json($response);
            }

            return response()->json(['error' => 'Invalid verification request'], 400);
        }
        // Handle Fitbit and other services
        $response = $this->tracker->get($sourceSlug)->verifyWebhook($request->get('verify'));

        if ($response) {
            return response()->noContent(204);
        }

        return response()->json(['error' => 'Verification failed'], 404);

    }

    // TODO: Check is ShopifyService is needed or not
    public function webhookAction(Request $request, SopifyRepository $sopifyRepository, EventService $eventService, $sourceSlug = 'fitbit')
    {
        Log::debug('TrackerWebhooksController: Webhook action', [
            'sourceSlug' => $sourceSlug,
            'method' => $request->method(),
            'params' => $request->all(),
        ]);

        // Handle Strava GET verification requests directly
        if ($sourceSlug === 'strava' && $request->method() === 'GET' && $request->has('hub_challenge')) {
            // Check if this is a valid Strava webhook verification request
            if ($request->input('hub_mode') === 'subscribe') {
                // Verify token is optional in the verification process
                if ($request->has('hub_verify_token') && $request->input('hub_verify_token') !== 'StravaForRTETracker') {
                    Log::warning('TrackerWebhooksController: Invalid verify token', [
                        'received' => $request->input('hub_verify_token'),
                        'expected' => 'StravaForRTETracker',
                    ]);
                }

                // Return the challenge value as required by Strava
                return response()->json(['hub.challenge' => $request->input('hub_challenge')]);
            }

            Log::warning('TrackerWebhooksController: Invalid hub_mode', [
                'received' => $request->input('hub_mode'),
                'expected' => 'subscribe',
            ]);

            return response()->json(['error' => 'Invalid verification request'], 400);
        }

        // Handle Fitbit verification
        if ($sourceSlug === 'fitbit' && isset($request->all()['verify'])) {
            return $this->verifyWebhook($request, $sourceSlug);
        }

        try {
            $tracker = $this->tracker->get($sourceSlug);

            // Special handling for Strava webhook events
            if ($sourceSlug === 'strava' && $request->method() === 'POST') {
                Log::debug('TrackerWebhooksController: Processing Strava webhook event', [
                    'payload' => $request->all(),
                ]);

                try {
                    // Process the Strava webhook event
                    $result = $tracker->processWebhook($request->all());
                    Log::debug('TrackerWebhooksController: Strava webhook processed', ['result' => $result]);

                    // If we have activity data and a user, create/update profile points
                    if (isset($result['activity']) && isset($result['user']) && isset($result['sourceProfile'])) {
                        $activity = $result['activity'];
                        $user = $result['user'];
                        $sourceProfile = $result['sourceProfile'];

                        // Create user participation points
                        $activity['dataSourceId'] = $sourceProfile->data_source_id;
                        $eventService->createUserParticipationPoints($user, $activity);

                        // Create or update user profile point
                        if (isset($activity['raw_distance']) && isset($activity['date'])) {
                            $this->createOrUpdateUserProfilePoint(
                                $user,
                                $activity['raw_distance'],
                                $activity['date'],
                                $sourceProfile,
                            );

                            Log::debug('TrackerWebhooksController: Updated user profile points for Strava activity', [
                                'user_id' => $user->id,
                                'distance' => $activity['raw_distance'],
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

            $notifications = $tracker->formatWebhookRequest($request);

            foreach ($notifications as $notification) {
                $user = $notification->user;

                if (is_null($user)) {
                    Log::debug("{$sourceSlug} : User Not Found", (array) $notification);

                    continue;
                }

                if (is_null($notification->sourceToken)) {
                    Log::debug("{$sourceSlug} : access token not found", (array) $notification);

                    continue;
                }

                $activities = $tracker->setSecrets($notification->sourceToken)
                    ->processWebhook($notification->webhookUrl)
                    ->setDate($notification->date)->activities();
                Log::debug("Webhook {$sourceSlug} Activities: ", ['activities' => $activities]);
                if ($activities->count()) {
                    foreach ($activities as $activity) {
                        $activity['dataSourceId'] = $notification->dataSourceId;
                        $eventService->createUserParticipationPoints($user, $activity);
                        $this->createOrUpdateUserProfilePoint($user, $activity['raw_distance'], $activity['date'], $notification->sourceProfile);
                        if ($sourceSlug === 'fitbit') {
                            $sopifyRepository->updateStatus($user->email, true);
                        }
                    }
                }
            }

            return response()->noContent(204);
        } catch (Exception $e) {
            Log::error('Error processing webhook', [
                'sourceSlug' => $sourceSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    private function createOrUpdateUserProfilePoint($user, $distance, $date, $sourceProfile, $type = 'webhook', $actionType = 'auto')
    {
        $profilePoint = $user->profilePoints()->where('date', $date)->where('data_source_id', $sourceProfile->data_source_id)->first();

        $data = [
            "{$type}_distance_km" => $distance,
            "{$type}_distance_mile" => ($distance * 0.621371),
            'date' => $date,
            'data_source_id' => $sourceProfile->data_source_id,
            'action_type' => $actionType,
        ];

        if ($profilePoint) {
            $profilePoint->fill($data)->save();
        } else {
            $user->profilePoints()->create($data);
        }
    }
}
