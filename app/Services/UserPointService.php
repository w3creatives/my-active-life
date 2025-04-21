<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Repositories\UserPointRepository;

class UserPointService
{

    private $userPointRepository;

    public function __construct(UserPointRepository $userPointRepository)
    {
        $this->userPointRepository = $userPointRepository;
    }

    public function createOrUpdate(object $user, array $point, bool $skipUpdate = false)
    {
        $point['date'] = Carbon::parse($point['date'])
            ->setTimezone($user->time_zone ?? 'UTC');

        $condition = $skipUpdate ? [] : [
            'date' => $point['date'],
            'modality' => $point['modality'],
            'event_id' => $point['eventId'],
            'data_source_id' => $point['dataSourceId']
        ];

        $this->userPointRepository->create($user, $point, $condition);
    }
}
