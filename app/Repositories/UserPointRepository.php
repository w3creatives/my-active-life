<?php

namespace App\Repositories;

class UserPointRepository
{

    public function create($user, $point, $condition = [])
    {

        if (empty($condition)) {
            return $user->points()->create($point);
        }

        return $user->points()->updateOrCreate($condition, $point);
    }
}
