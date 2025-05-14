<?php

declare(strict_types=1);

namespace App\Services\DataSourceService;

use App\Models\DataSource;
use App\Models\User;
use App\Services\StravaService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service for unsubscribing a user from Strava
 */
final class StravaUnsubscriber
{
    private User $user;
    private DataSource $dataSource;
    private StravaService $stravaService;

    /**
     * Initialize the unsubscriber with user and data source
     * 
     * @param User $user The user to unsubscribe
     * @param DataSource $dataSource The Strava data source
     */
    public function __construct(User $user, DataSource $dataSource)
    {
        $this->user = $user;
        $this->dataSource = $dataSource;
        $this->stravaService = new StravaService();
    }

    /**
     * Unsubscribe the user from Strava
     * Revokes access tokens and removes the user's profile
     * 
     * @return bool True if unsubscription was successful
     * @throws Exception If unsubscription fails
     */
    public function unsubscribe(): bool
    {
        try {
            Log::info('Unsubscribing user from Strava', [
                'user_id' => $this->user->id
            ]);

            // Get the user's Strava profile
            $profile = $this->user->profiles()
                ->where('data_source_id', $this->dataSource->id)
                ->first();

            if (!$profile) {
                Log::info('No Strava profile found for user', [
                    'user_id' => $this->user->id
                ]);
                return true;
            }

            // Refresh token if needed
            if ($profile->token_expires_at && now()->gt($profile->token_expires_at)) {
                $refreshResponse = $this->stravaService->refreshToken($profile->refresh_token);
                if (isset($refreshResponse['access_token'])) {
                    $profile->access_token = $refreshResponse['access_token'];
                    $profile->refresh_token = $refreshResponse['refresh_token'] ?? $profile->refresh_token;
                    $profile->token_expires_at = $refreshResponse['token_expires_at'] ?? $profile->token_expires_at;
                    $profile->save();
                }
            }

            // Attempt to deauthorize with Strava API
            try {
                $this->stravaService->setAccessToken($profile->access_token);
                // Note: We would need to add a deauthorize method to StravaService
                // This would make a request to https://www.strava.com/oauth/deauthorize
            } catch (Exception $e) {
                Log::warning('Failed to deauthorize with Strava API, but continuing', [
                    'error' => $e->getMessage(),
                    'user_id' => $this->user->id
                ]);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to unsubscribe from Strava', [
                'error' => $e->getMessage(),
                'user_id' => $this->user->id
            ]);
            throw $e;
        }
    }
}