<?php

namespace App\Repositories;

trait UserPointRepository
{

    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function create($point, $condition = [])
    {

        if (empty($condition)) {
            return $this->user->points()->create($point);
        }

        return $this->user->points()->updateOrCreate($condition, $point);
    }
}
