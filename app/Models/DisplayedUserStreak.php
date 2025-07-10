<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DisplayedUserStreak extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function streak(): BelongsTo
    {
        return $this->belongsTo(EventStreak::class, 'event_streak_id', 'id');
    }
}
