<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\DataSourceInterface;
use App\Models\DataSource;
use App\Models\DataSourceProfile;
use App\Services\DataSourceService\FitbitUnsubscriber;
use App\Services\DataSourceService\GarminUnsubscriber;
use App\Services\DataSourceService\StravaUnsubscriber;
use App\Services\EventService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

final class DeviceSyncController extends Controller
{
    private $tracker;

    public function __construct()
    {
        $this->tracker = app(DataSourceInterface::class);
    }

    public function index(): Response
    {
        $user = auth()->user();

        // Get user's connected data sources
        $connectedSources = DataSourceProfile::where('user_id', $user->id)
            ->with('source')
            ->get()
            ->pluck('source.short_name')
            ->toArray();

        return Inertia::render('settings/device-sync', [
            'connectedSources' => $connectedSources,
        ]);
    }

    public function connect(Request $request, string $sourceSlug): RedirectResponse
    {
        // Store sync start date in session if provided
        if ($request->has('sync_start_date')) {
            session(['sync_start_date' => $request->get('sync_start_date')]);
        }

        return redirect($this->tracker->get($sourceSlug)->authUrl());
    }

    public function trackerCallback(Request $request, EventService $eventService, string $sourceSlug): RedirectResponse
    {
        if (! in_array($sourceSlug, ['garmin', 'strava', 'fitbit'])) {
            throw new Exception('Invalid request');
        }

        if ($sourceSlug === 'garmin') {
            $authCode = [$request->get('oauth_token'), $request->get('oauth_verifier')];
        } else {
            $authCode = [$request->get('code')];
        }

        $response = $this->tracker->get($sourceSlug)->authorize($authCode)->response();

        if (! $response) {
            return redirect()->route('profile.device-sync.edit');
        }

        $user = $request->user();

        // For Strava, we need to store the user_id as access_token_secret for webhook identification
        if ($sourceSlug === 'strava' && isset($response['user_id'])) {
            $this->tracker->get($sourceSlug)
                ->setAccessToken($response['access_token'])
                ->setAccessTokenSecret($response['user_id'])
                ->subscribe($user->id, $response['user_id']);
        }

        // Get source profile from App\Models\User.php
        $dataSource = DataSource::where('short_name', $sourceSlug)->first();

        $userSourceProfile = $user->profiles()->where('data_source_id', $dataSource->id)->first();

        $response['data_source_id'] = $dataSource->id;

        // For Strava, store the user_id as access_token_secret for webhook identification
        if ($sourceSlug === 'strava' && isset($response['user_id'])) {
            $response['access_token_secret'] = $response['user_id'];
        }

        if (! is_null($userSourceProfile)) {
            $userSourceProfile->fill($response)->save();

            // For Strava, create webhook subscription if needed
            if ($sourceSlug === 'strava') {
                try {
                    $this->tracker->get($sourceSlug)->createSubscription();
                } catch (Exception $e) {
                    Log::error('Failed to create Strava subscription', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id,
                    ]);
                }
            }

            return redirect()->route('profile.device-sync.edit');
        }

        // For Strava, create a webhook subscription if needed
        if ($sourceSlug === 'strava') {
            try {
                $this->tracker->get($sourceSlug)->createSubscription();
            } catch (Exception $e) {
                Log::error('Failed to create Strava subscription', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
            }
        }

        if (session()->has('sync_start_date')) {
            $startDate = session()->get('sync_start_date');
            session()->forget('sync_start_date');

            $userSourceProfile = $user->profiles()->create($response);
            $activities = $this->tracker->get($sourceSlug)
                ->setSecrets([$userSourceProfile->access_token, $userSourceProfile->access_token_secret])
                ->setDate($startDate)
                ->activities();

            if ($activities->count() && $sourceSlug !== 'garmin') {
                foreach ($activities as $activity) {
                    $activity['dataSourceId'] = $userSourceProfile->data_source_id;
                    $eventService->createUserParticipationPoints($user, $activity);
                }
            }
        }

        return redirect()->route('profile.device-sync.edit');
    }

    /**
     * Disconnect a data source from the user's account
     */
    public function disconnect(Request $request, string $sourceSlug): RedirectResponse
    {
        $user = $request->user();
        $dataSource = DataSource::where('short_name', $sourceSlug)->first();

        if (! $dataSource) {
            return redirect()->back()->with('error', 'Data source not found');
        }

        // Find the user's data source profile
        $profile = DataSourceProfile::where('user_id', $user->id)
            ->where('data_source_id', $dataSource->id)
            ->first();

        if (! $profile) {
            return redirect()->back()->with('error', 'You are not connected to this data source');
        }

        // Check if user wants to delete synced miles
        $deleteData = $request->input('delete_data', 'no');

        // Revoke access with the provider if needed
        try {
            switch ($sourceSlug) {
                case 'fitbit':
                    // Call the Fitbit unsubscriber service
                    // $unsubscriber = new FitbitUnsubscriber($user, $dataSource);
                    // $unsubscriber->unsubscribe();
                    break;
                case 'garmin':
                    // Call the Garmin unsubscriber service
                    // $unsubscriber = new GarminUnsubscriber($user, $dataSource);
                    // $unsubscriber->unsubscribe();
                    break;
                case 'strava':
                    // Call the Strava unsubscriber service
                    $unsubscriber = new StravaUnsubscriber($user, $dataSource);
                    $unsubscriber->unsubscribe();

                    // Additionally, try to deauthorize with Strava API directly
                    try {
                        // Refresh token if needed
                        if ($profile->token_expires_at && now()->gt($profile->token_expires_at)) {
                            $refreshResponse = $this->tracker->get($sourceSlug)->refreshToken($profile->refresh_token);
                            if (isset($refreshResponse['access_token'])) {
                                $profile->access_token = $refreshResponse['access_token'];
                                $profile->refresh_token = $refreshResponse['refresh_token'] ?? $profile->refresh_token;
                                $profile->token_expires_at = $refreshResponse['token_expires_at'] ?? $profile->token_expires_at;
                                $profile->save();
                            }
                        }

                        // Make deauthorization request to Strava
                        $response = $this->tracker->get($sourceSlug)
                            ->setAccessToken($profile->access_token)
                            ->deauthorize();

                        Log::info('Strava deauthorization successful', [
                            'user_id' => $user->id,
                            'response' => $response,
                        ]);
                    } catch (Exception $e) {
                        Log::error('Strava deauthorization failed but continuing with profile deletion', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                        ]);
                    }
                    break;
                case 'apple':
                    // Apple Health disconnection is handled differently
                    break;
            }

            // Delete the profile
            $profile->delete();

            // If user chose to delete synced miles, delete them
            if ($deleteData === 'yes') {
                // Get the EventService to handle deleting synced miles
                $eventService = app(\App\Services\EventService::class);
                $eventService->deleteSourceSyncedMile($user, $dataSource->id);
            }

            return redirect()->back()->with('success', ucfirst($sourceSlug).' disconnected successfully');
        } catch (Exception $e) {
            Log::error('Failed to disconnect data source', [
                'error' => $e->getMessage(),
                'source' => $sourceSlug,
                'user_id' => $user->id,
            ]);

            return redirect()->back()->with('error', 'Failed to disconnect: '.$e->getMessage());
        }
    }
}
