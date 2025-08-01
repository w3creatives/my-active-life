<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait DeviceLoggerTrait
{
    private $logChannel = 'device';
    private function logger($profile, $message, $response)
    {

        Log::channel($this->logChannel)->info($message, [
            'userId' => $profile->user_id,
            'isSuccessful' => $response->successful(),
            'response' => $response->body(),
        ]);

    }
}
