<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DataSourceProfile;
use App\Services\EventService;
use App\Services\OuraRingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SyncOuraRingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oura:sync-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Oura Ring daily_activity and workout data for the current date';

    /**
     * Execute the console command.
     */
    public function handle(EventService $eventService): int
    {
        $this->info('Starting Oura Ring data sync for today...');

        $today = '2025-12-22'; // Carbon::today()->format('Y-m-d');
        $syncedCount = 0;
        $errorCount = 0;

        // Get all active Oura Ring data source profiles
        $ouraProfiles = DataSourceProfile::whereHas('source', function ($query) {
            return $query->where('short_name', 'ouraring');
        })->with(['user', 'source'])->get();

        if ($ouraProfiles->isEmpty()) {
            $this->warn('No Oura Ring profiles found.');

            return self::SUCCESS;
        }

        $this->info("Found {$ouraProfiles->count()} Oura Ring profiles to sync.");

        foreach ($ouraProfiles as $profile) {
            try {
                $user = $profile->user;

                if (! $user) {
                    $this->warn("Profile ID {$profile->id} has no associated user. Skipping.");

                    continue;
                }

                // Refresh token if needed
                if ($profile->token_expires_at && Carbon::parse($profile->token_expires_at)->lt(Carbon::now())) {
                    $this->info("Refreshing expired token for user {$user->email}...");
                    $ouraService = new OuraRingService;
                    $newTokens = $ouraService->refreshToken($profile->refresh_token);

                    if (! empty($newTokens)) {
                        $profile->update($newTokens);
                        $profile->refresh();
                        $this->info('Token refreshed successfully.');
                    } else {
                        $this->error("Failed to refresh token for user {$user->email}. Skipping.");

                        continue;
                    }
                }

                // Fetch today's activities
                $this->info("Syncing data for user: {$user->email}");

                $ouraService = new OuraRingService($profile->access_token);

                // Fetch activities for today with adjustment logic
                $activities = $ouraService
                    ->setProfile($profile)
                    ->setDate('2025-11-01', '2025-12-24')
                    ->activities();

                $this->info("  Found {$activities->count()} activities for {$today}");

                if ($activities->count() > 0) {
                    foreach ($activities as $activity) {
                        // Add data source ID to activity
                        $activity['dataSourceId'] = $profile->data_source_id;

                        // Create user participation points
                        $eventService->createUserParticipationPoints($user, $activity);

                        // Create or update user profile point
                        if (isset($activity['raw_distance']) && isset($activity['date'])) {
                            $this->createOrUpdateUserProfilePoint(
                                $user,
                                $activity['raw_distance'],
                                $activity['date'],
                                $profile,
                                'cron',
                                'auto'
                            );

                            $this->info("  - {$activity['modality']}: {$activity['distance']} miles on {$activity['date']}");
                        }
                    }

                    $syncedCount++;
                    $this->info("  ✓ Successfully synced user {$user->email}");
                } else {
                    $this->info('  - No activities found for today');
                }

            } catch (Exception $e) {
                $errorCount++;
                $this->error("Error syncing user ID {$profile->user_id}: {$e->getMessage()}");
                Log::error('SyncOuraRingData: Error syncing user', [
                    'user_id' => $profile->user_id,
                    'profile_id' => $profile->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("Sync completed. Synced: {$syncedCount}, Errors: {$errorCount}");

        return self::SUCCESS;
    }

    /**
     * Create or update user profile point for a given date and data source
     *
     * @param  mixed  $user  The user model
     * @param  float  $distance  The distance in kilometers or steps
     * @param  string  $date  The activity date
     * @param  mixed  $sourceProfile  The data source profile
     * @param  string  $type  The type of sync (webhook, manual, cron, etc.)
     * @param  string  $actionType  The action type (auto, manual)
     */
    private function createOrUpdateUserProfilePoint($user, $distance, $date, $sourceProfile, $type = 'cron', $actionType = 'auto'): void
    {
        $profilePoint = $user->profilePoints()
            ->where('date', $date)
            ->where('data_source_id', $sourceProfile->data_source_id)
            ->first();

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
