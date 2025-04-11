<?php

namespace App\Jobs;

use App\Models\DataSourceProfile;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUserDistanceTracker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        Log::info('ProcessUserDistanceTracker: Job queued at ' . Carbon::now()->toDateTimeString());
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessUserDistanceTracker: Starting job at ' . Carbon::now()->toDateTimeString());
        Log::info('ProcessUserDistanceTracker: Queue connection: ' . config('queue.default') . ', Queue name: ' . ($this->queue ?? 'default'));
        
        $profiles = DataSourceProfile::whereHas('source', function ($query) {
            return $query->where('short_name', 'fitbit');
        })
            ->where(function ($query) {
                return $query->whereNull('last_run_at')->orWhere('last_run_at', '<=', Carbon::now()->subHours(23));
            })
            ->whereHas('user.participations', function ($query) {
                $query->where('subscription_end_date', '>=', Carbon::now()->format('Y-m-d'))
                    ->whereHas('event', function ($eventQuery) {
                        $eventQuery->where('open', true);
                    });
            })
            ->get();

        Log::info('ProcessUserDistanceTracker: Found ' . $profiles->count() . ' profiles to process');

        foreach ($profiles as $profile) {
            // Dispatch a job for each profile to process in parallel
            ProcessUserProfile::dispatch($profile);
        }
    }
}
