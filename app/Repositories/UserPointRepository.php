<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserPointRepository
{
    public function create($user, $point, $condition = [])
    {

        if (empty($condition)) {
            return $user->points()->create($point);
        }

        return $user->points()->updateOrCreate($condition, $point);
    }
}
