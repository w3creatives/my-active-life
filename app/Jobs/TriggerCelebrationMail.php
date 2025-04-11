<?php

namespace App\Jobs;

use App\Models\EventParticipation;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TriggerCelebrationMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        Log::info('TriggerCelebrationMail: Job queued at ' . Carbon::now()->toDateTimeString());
    }

    /**
     * Execute the job.
     */
    public function handle(MailService $mailService): void
    {
        Log::info('TriggerCelebrationMail: Starting job at ' . Carbon::now()->toDateTimeString());
        Log::info('TriggerCelebrationMail: Queue connection: ' . config('queue.default') . ', Queue name: ' . ($this->queue ?? 'default'));

        $currentDate = Carbon::now()->format('Y-m-d');

        $participations = EventParticipation::where('subscription_end_date', '>=', $currentDate)
            ->whereHas('event', function ($query) use ($currentDate) {
                return $query->where('start_date', '<=', $currentDate);
            })
            ->get();

        Log::info('TriggerCelebrationMail: Found ' . $participations->count() . ' participations to process');

        foreach ($participations as $participation) {
            try {
                $mailService->sendCelebrationMail($participation->event_id, $participation->user_id);
                Log::info('TriggerCelebrationMail: Sent celebration mail for event ID: ' . $participation->event_id . ', user ID: ' . $participation->user_id);
            } catch (\Exception $e) {
                Log::error('TriggerCelebrationMail: Error sending celebration mail for event ID: ' . $participation->event_id . ', user ID: ' . $participation->user_id . ': ' . $e->getMessage());
            }
        }
    }
}
