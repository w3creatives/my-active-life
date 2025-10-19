<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\DataSourceInterface;
use App\Models\DataSource;
use App\Models\DataSourceProfile;
use App\Services\DeviceService;
use App\Services\EventService;
use Carbon\Carbon;
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
        $syncStartDate = null;

        if (session()->has('sync_start_date')) {
            $syncStartDate = session()->get('sync_start_date');
            session()->forget('sync_start_date');
        }

        if (! in_array($sourceSlug, ['garmin', 'strava', 'fitbit', 'ouraring'])) {
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

        // Log::info('DeviceSyncController:trackerCallback: Response: '.json_encode($response));

        // For Strava, Fitbit, and Oura Ring, we need to store the user_id as access_token_secret for webhook identification
        if (in_array($sourceSlug, ['strava', 'fitbit', 'ouraring']) && isset($response['user_id'])) {
            $subscribe = $this->tracker->get($sourceSlug)
                ->setAccessToken($response['access_token'])
                ->setAccessTokenSecret($response['user_id'])
                ->subscribe($user->id, $response['user_id']);

            Log::info('DeviceSyncController:trackerCallback: Subscribe: '.json_encode($subscribe));
        }

        // Get source profile from App\Models\User.php
        $dataSource = DataSource::where('short_name', $sourceSlug)->first();

        $userSourceProfile = $user->profiles()->where('data_source_id', $dataSource->id)->first();

        $response['data_source_id'] = $dataSource->id;

        // For Strava and Oura Ring, store the user_id as access_token_secret for webhook identification
        if (in_array($sourceSlug, ['strava', 'ouraring']) && isset($response['user_id'])) {
            $response['access_token_secret'] = $response['user_id'];
        }

        if (! is_null($userSourceProfile)) {
            $userSourceProfile->fill($response)->save();

            // For Strava, create webhook subscription if needed
            /*
             * Duplicate - Refer to line no 89
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
            */
        } else {
            $userSourceProfile = $user->profiles()->create($response);
        }

        // For Strava, create a webhook subscription if needed
        // Duplicate - Refer to line no 89
        /*if ($sourceSlug === 'strava') {
            try {
                $this->tracker->get($sourceSlug)->createSubscription();
            } catch (Exception $e) {
                Log::error('Failed to create Strava subscription', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
            }
        }*/

        if ($syncStartDate) {

            $activities = $this->tracker->get($sourceSlug)
                ->setSecrets([$userSourceProfile->access_token, $userSourceProfile->access_token_secret])
                ->setDate($syncStartDate, Carbon::now()->format('Y-m-d'))
                ->activities();

            Log::info('DeviceSyncController:trackerCallback: Activities: '.json_encode($activities));

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
    public function disconnect(Request $request, string $sourceSlug, DeviceService $deviceService): RedirectResponse
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
            $deviceService->revoke($profile);

            // Delete the profile
            $profile->delete();

            // If user chose to delete synced miles, delete them
            if ($deleteData === 'yes') {
                // Get the EventService to handle deleting synced miles
                $eventService = app(EventService::class);
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
