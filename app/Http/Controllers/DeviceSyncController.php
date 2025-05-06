<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\DataSourceInterface;
use App\Models\DataSource;
use App\Models\DataSourceProfile;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class DeviceSyncController extends Controller
{
    private $tracker;

    public function __construct()
    {
        $this->tracker = app(DataSourceInterface::class);
    }

    public function index()
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

    public function connect(Request $request, string $sourceSlug)
    {
        return redirect($this->tracker->get($sourceSlug)->authUrl());
    }

    public function trackerCallback(Request $request, string $sourceSlug)
    {

        if ($sourceSlug === 'garmin') {
            $authCode =  [$request->get('oauth_token'), $request->get('oauth_verifier')];
        } else {
            $authCode =  $request->get('code');
        }

        $deviceConnectionResponse = $this->tracker->get($sourceSlug)->authorize($authCode)->response();

        if(!$deviceConnectionResponse) {
            //Add logic to redirect back with error message
            dd("ERROR");
        }


        /*
        if ($sourceSlug === 'garmin') {
            $deviceConnectionResponse = $this->tracker->get($sourceSlug)->authorize([$request->get('oauth_token'), $request->get('oauth_verifier')])->response();
        } else {
            $deviceConnectionResponse = $this->tracker->get($sourceSlug)->authorize($request->get('code'))->response();
        }*/

        // Store connection in DataSourceProfile table
        $user = auth()->user();
        $dataSource = DataSource::where('short_name', $sourceSlug)->first();

        if ($dataSource) {
            // Check if the profile already exists
            $profile = DataSourceProfile::where('user_id', $user->id)
                ->where('data_source_id', $dataSource->id)
                ->first();

            $profileData = [
                'user_id' => $user->id,
                'data_source_id' => $dataSource->id,
                'access_token' => $deviceConnectionResponse->access_token ?? null,
                'refresh_token' => $deviceConnectionResponse->refresh_token ?? null,
                'token_expires_at' => isset($deviceConnectionResponse->expires_at)
                    ? now()->addSeconds($deviceConnectionResponse->expires_at)
                    : (isset($deviceConnectionResponse->expires_in)
                        ? now()->addSeconds($deviceConnectionResponse->expires_in)
                        : null),
                'access_token_secret' => $deviceConnectionResponse->access_token_secret ?? null,
            ];
           
            if ($profile) {
                $profile->update($profileData);
            } else {
                DataSourceProfile::create($profileData);
            }
        }

        return redirect()->route('profile.device-sync.edit');
    }

    /**
     * Disconnect a data source from the user's account
     *
     * @return RedirectResponse
     */
    public function disconnect(Request $request, string $sourceSlug)
    {
        $user = auth()->user();
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

        // Revoke access with the provider if needed
        try {
            /**
            switch ($sourceSlug) {
                case 'fitbit':
                    // Call the Fitbit unsubscriber service
                    $unsubscriber = new \App\Services\DataSourceService\FitbitUnsubscriber($user, $dataSource);
                    $unsubscriber->unsubscribe();
                    break;
                case 'garmin':
                    // Call the Garmin unsubscriber service
                    $unsubscriber = new \App\Services\DataSourceService\GarminUnsubscriber($user, $dataSource);
                    $unsubscriber->unsubscribe();
                    break;
                case 'strava':
                    // Call the Strava unsubscriber service
                    $unsubscriber = new \App\Services\DataSourceService\StravaUnsubscriber($user, $dataSource);
                    $unsubscriber->unsubscribe();
                    break;
                case 'apple':
                    // Apple Health disconnection is handled differently
                    break;
            }
             */

            // Delete the profile
            $profile->delete();

            return redirect()->back()->with('success', ucfirst($sourceSlug) . ' disconnected successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to disconnect: ' . $e->getMessage());
        }
    }
}
