<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventStreak extends Model
{
    protected $guarded = [];

    public function getMinDistanceAttribute(){
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['min_distance'] ?? null;
    }

}
