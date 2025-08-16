<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DataSourceProfile;
use App\Services\EventService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessUserProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The profile instance.
     *
     * @var DataSourceProfile
     */
    protected $profile;

    /**
     * Create a new job instance.
     */
    public function __construct(DataSourceProfile $profile)
    {
        $this->profile = $profile;
        Log::info('ProcessUserProfile: Job queued for user ID: '.$profile->user_id.' at '.Carbon::now()->toDateTimeString());
    }

    /**
     * Execute the job.
     */
    public function handle(EventService $eventService): void
    {
        Log::info('ProcessUserProfile: Starting job at '.Carbon::now()->toDateTimeString());
        Log::info('ProcessUserProfile: Processing profile for user ID: '.$this->profile->user_id.' on queue: '.($this->queue ?? 'default'));

        try {
            $accessToken = $this->profile->access_token;

            if (Carbon::parse($this->profile->token_expires_at)->lte(Carbon::now())) {
                $userSourceProfile = $this->fitbitRefreshToken($this->profile);

                Log::info('ProcessUserProfile: Access token expired for user ID: '.$this->profile->user_id.', refreshing token.');

                if ($userSourceProfile) {
                    $accessToken = $userSourceProfile->access_token;
                    Log::info('ProcessUserProfile: Token refreshed successfully for user ID: '.$this->profile->user_id);
                } else {
                    Log::error('ProcessUserProfile: Failed to refresh token for user ID: '.$this->profile->user_id);

                    return;
                }
            }

            try {
                $startDate = $this->profile->sync_start_date;
                $endDate = Carbon::now()->format('Y-m-d');
                $response = Http::withToken($accessToken)->get("https://api.fitbit.com/1/user/-/activities/distance/date/{$startDate}/{$endDate}.json");

                if ($response->unauthorized()) {
                    Log::error('ProcessUserProfile: Unauthorized: '.$this->profile->user_id, ['data' => $response->body()]);

                    return;
                }

                $dateDistances = $response->json('activities-distance');

                if (! $dateDistances) {
                    $dateDistances = [];
                }

                if (! count($dateDistances)) {
                    return;
                }

                foreach ($dateDistances as $data) {
                    $distance = $data['value'];
                    $date = $data['dateTime'];

                    $this->createOrUpdateUserProfilePoint($this->profile->user, $distance, $date, $this->profile, 'cron', 'manual');
                    if (! $distance) {
                        $distance = 0;
                    }

                    $distance = $distance * 0.621371;
                    try {
                        $this->createPoints($eventService, $this->profile->user, $date, $distance, $this->profile);
                    } catch (Exception $e) {
                        Log::error('ProcessUserProfile: Error creating points for user ID: '.$this->profile->user_id.' on '.$date.': '.$e->getMessage());
                    }
                }

                $this->profile->fill(['last_run_at' => Carbon::now()])->save();

            } catch (Exception $e) {
                Log::error('ProcessUserProfile: Error fetching Fitbit data for user ID: '.$this->profile->user_id.': '.$e->getMessage());
                $this->fitbitRefreshToken($this->profile);
            }
        } catch (Exception $e) {
            Log::error('ProcessUserProfile: Unexpected error for user ID: '.$this->profile->user_id.': '.$e->getMessage());
        }
    }

    private function fitbitRefreshToken($profile)
    {
        Log::info('ProcessUserProfile: Attempting to refresh Fitbit token for user ID: '.$profile->user_id);

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode(env('FITBIT_CLIENT_ID').':'.env('FITBIT_CLIENT_SECRET')),
            ])
            ->post('https://api.fitbit.com/oauth2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $profile->refresh_token,
            ]);

        $data = json_decode($response->body(), true);

        if (isset($data['access_token'])) {
            $profileData = collect($data)->only(['access_token', 'refresh_token'])->toArray();
            $profileData['token_expires_at'] = Carbon::now()->addSeconds($data['expires_in'])->format('Y-m-d H:i:s');

            $profile->fill($profileData)->save();

            return $profile;
        }

        Log::error('ProcessUserProfile: Failed to refresh Fitbit token for user ID: '.$profile->user_id.'. Response: '.$response->body());

        return false;
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

    private function createPoints($eventService, $user, $date, $distance, $sourceProfile)
    {
        if (! $distance) {
            return false;
        }

        $currentDate = Carbon::now()->format('Y-m-d');

        $participations = $user->participations()->where('subscription_end_date', '>=', $currentDate)->whereHas('event', function ($query) use ($currentDate) {
            return $query->where('start_date', '<=', $currentDate);
        })->get();

        foreach ($participations as $participation) {
            $pointdata = ['amount' => $distance, 'date' => $date, 'event_id' => $participation->event_id, 'modality' => 'other', 'data_source_id' => $sourceProfile->data_source_id];

            $userPoint = $user->points()->where(['date' => $date, 'modality' => 'other', 'event_id' => $participation->event_id, 'data_source_id' => $sourceProfile->data_source_id])->first();

            if ($userPoint) {
                $userPoint->update($pointdata);
            } else {
                $user->points()->create($pointdata);
            }

            $eventService->createOrUpdateUserPoint($user, $participation->event_id, $date);
        }

        return true;
    }
}
