<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TeamAchievement extends Model
{
    protected $guarded = [];

    public function scopeHasEvent($query, $eventId)
    {

        return $query->where('event_id', $eventId);
    }
}
