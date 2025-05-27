<?php

declare(strict_types=1);

namespace App\Services;

final class FollowService
{
    public function getUserTeamFollowing(User $user, int $eventId)
    {
        $user->teamFollowings();
    }
}
