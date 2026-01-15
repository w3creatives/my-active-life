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

        if ($sourceSlug === 'ouraring') {
            Log::debug('TrackerWebhooksController: Oura Ring verification request', [
                'query_params' => $request->query(),
            ]);

            // Handle Oura Ring webhook verification
            $response = $this->tracker->get($sourceSlug)->verifyWebhook($request->all());

            if ($response && is_array($response)) {
                Log::info('TrackerWebhooksController: Oura Ring verification successful');

                return response()->json($response, 200);
            }

            Log::warning('TrackerWebhooksController: Oura Ring verification failed');

            return response()->json(['error' => 'Invalid verification token'], 401);
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
            'headers' => $request->headers->all(),
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

        // Handle Oura Ring GET verification
        if ($sourceSlug === 'ouraring' && $request->method() === 'GET' && $request->has('verification_token')) {
            return $this->verifyWebhook($request, $sourceSlug);
        }

        // Handle Oura Ring POST webhook with signature verification
        if ($sourceSlug === 'ouraring' && $request->method() === 'POST') {
            $signature = $request->header('X-Oura-Signature');
            $timestamp = $request->header('X-Oura-Timestamp');
            $payload = $request->getContent();

            $tracker = $this->tracker->get($sourceSlug);

            // Verify HMAC signature
            /**
            if (! $signature || ! $timestamp || ! $tracker->verifyWebhookSignature($signature, $timestamp, $payload)) {
                Log::warning('TrackerWebhooksController: Invalid Oura Ring signature', [
                    'has_signature' => (bool) $signature,
                    'has_timestamp' => (bool) $timestamp,
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }
             */

            // payload has: {"payload":{"event_type":"create","data_type":"workout","object_id":"377ced8f-b6d5-4671-b01b-74a7e0cbbf58","event_time":"2025-12-21T20:55:32.938000+00:00","user_id":"518354fe-b6e1b2378f4e1ae68e3613f9-1c"}}
            Log::debug('TrackerWebhooksController: Processing Oura Ring webhook event', [
                'payload' => $request->all(),
            ]);

            try {
                // Process the Oura Ring webhook event
                $result = $tracker->processWebhook($request->all());
                Log::debug('TrackerWebhooksController: Oura Ring webhook processed', ['result' => $result]);

                // If we have a user and source profile, fetch and process activities
                if (isset($result['user']) && isset($result['sourceProfile']) && isset($result['data_type']) && isset($result['object_id'])) {
                    $user = $result['user'];
                    $sourceProfile = $result['sourceProfile'];
                    $dataType = $result['data_type'];
                    $objectId = $result['object_id'];

                    // Only process workout and daily_activity events
                    if (in_array($dataType, ['workout', 'daily_activity'])) {
                        // Fetch the actual activity data from Oura Ring API
                        $activity = $tracker->setAccessToken($sourceProfile->access_token)
                            ->fetchWebhookData($dataType, $objectId);

                        Log::debug('TrackerWebhooksController: Oura Ring activity fetched', [
                            'user_id' => $user->id,
                            'data_type' => $dataType,
                            'object_id' => $objectId,
                            'activity' => $activity,
                        ]);

                        // Process the activity if we successfully fetched it
                        if ($activity && ! empty($activity) && isset($activity['date'])) {
                            $activityDate = $activity['date'];

                            // Fetch ALL activities for this day to apply adjustment logic
                            // This prevents double-counting between daily_steps and workouts
                            $allActivities = $tracker->setAccessToken($sourceProfile->access_token)
                                ->setProfile($sourceProfile)
                                ->setDate($activityDate, $activityDate)
                                ->activities();

                            Log::debug('TrackerWebhooksController: Fetched all Oura Ring activities for the day with adjustment logic', [
                                'user_id' => $user->id,
                                'date' => $activityDate,
                                'activities_count' => $allActivities->count(),
                                'activities' => $allActivities->toArray(),
                            ]);

                            // Process each adjusted activity
                            foreach ($allActivities as $adjustedActivity) {
                                $adjustedActivity['dataSourceId'] = $sourceProfile->data_source_id;
                                $eventService->createUserParticipationPoints($user, $adjustedActivity);

                                // Create or update user profile point
                                if (isset($adjustedActivity['raw_distance']) && isset($adjustedActivity['date'])) {
                                    $this->createOrUpdateUserProfilePoint(
                                        $user,
                                        $adjustedActivity['raw_distance'],
                                        $adjustedActivity['date'],
                                        $sourceProfile,
                                        'webhook',
                                        'auto'
                                    );

                                    Log::debug('TrackerWebhooksController: Updated user profile points for Oura Ring activity', [
                                        'user_id' => $user->id,
                                        'distance' => $adjustedActivity['raw_distance'],
                                        'date' => $adjustedActivity['date'],
                                        'modality' => $adjustedActivity['modality'] ?? 'unknown',
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Always return 200 OK to Oura Ring
                return response()->json(['status' => 'success'], 200);
            } catch (Exception $e) {
                Log::error('TrackerWebhooksController: Error processing Oura Ring webhook', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Still return 200 OK to Oura Ring to prevent retries
                return response()->json(['status' => 'error_handled'], 200);
            }
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
