<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Interfaces\DataSourceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Repositories\SopifyRepository;
use App\Services\EventService;

class TrackerWebhooksController extends Controller
{

    private $tracker;

    public function __construct()
    {
        $this->tracker = app(DataSourceInterface::class);
    }

    public function verifyWebhook(Request $request, $sourceSlug = 'fitbit')
    {
        return $this->tracker->get($sourceSlug)->verifyWebhook($request->get('verify'));
    }

    public function webhookAction(Request $request, SopifyRepository $sopifyRepository, EventService $eventService, $sourceSlug = 'fitbit')
    {

        $tracker = $this->tracker->get($sourceSlug);

        $notifications = $tracker->formatWebhookRequest($request);

        foreach ($notifications as $notification) {

            $user = $notification->user;

            if (is_null($user)) {
                Log::stack(['single'])->debug("{$sourceSlug} : User Not Found", $notification);
                continue;
            }


            if (is_null($notification->sourceToken)) {
                Log::stack(['single'])->debug("{$sourceSlug} : access token not found", $notification);
                continue;
            }

            $activities = $tracker->setSecrets($notification->sourceToken)
                ->processWebhook($notification->webhookUrl)
                ->setDate($notification->date)->activities();

            if ($activities->count()) {
                foreach ($activities as $activity) {

                    $activity['dataSourceId'] = $notification->dataSourceId;
                    $eventService->createUserParticipationPoints($user, $activity);

                    if ($sourceSlug == 'fitbit') {
                        $this->createOrUpdateUserProfilePoint($user, $activity['raw_distance'], $activity['date'], $notification->sourceProfile);
                        $sopifyRepository->updateStatus($user->email, true);
                    }
                }
            }
        }

        http_response_code(204);
    }

    private function createOrUpdateUserProfilePoint($user, $distance, $date, $sourceProfile, $type = 'webhook', $actionType = "auto")
    {

        $profilePoint = $user->profilePoints()->where('date', $date)->where('data_source_id', $sourceProfile->data_source_id)->first();

        $data = [
            "{$type}_distance_km" => $distance,
            "{$type}_distance_mile" => ($distance * 0.621371),
            'date' => $date,
            'data_source_id' => $sourceProfile->data_source_id,
            'action_type' => $actionType
        ];

        if ($profilePoint) {
            $profilePoint->fill($data)->save();
        } else {
            $user->profilePoints()->create($data);
        }
    }
}
