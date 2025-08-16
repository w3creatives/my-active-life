<?php

declare(strict_types=1);

namespace App\Actions\Points;

use App\Services\UserPointService;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateUserPoint
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
