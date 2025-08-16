<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UserAchievement extends Model
{
    protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeHasEvent($query, $eventId)
    {

        return $query->where('event_id', $eventId);
    }
}
