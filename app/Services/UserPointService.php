<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserPointRepository;

trait UserPointService
{

    use UserPointRepository;

    private $user;

    public function __construct(
        User $user
    ) {
        $this->user = $user;
    }

    public function createOrUpdate(array $point, bool $skipUpdate = false)
    {
        $condition = $skipUpdate ? [] : [
            'date' => $point['data'],
            'modality' => $point['modality'],
            'event_id' => $point['eventId'],
            'data_source_id' => $point['dataSourceId']
        ];

        $this->create($point, $condition);
    }
}
