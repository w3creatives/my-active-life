<?php

namespace App\Actions\Points;

use App\Services\UserPointService;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateUserPoint
{
    use AsAction;

    private $userPointService;

    public function __construct(UserPointService $userPointService)
    {
        $this->userPointService = $userPointService;
    }

    public function handle($user, $data)
    {
        $this->userPointService->createOrUpdate($user, $data);
    }
}
