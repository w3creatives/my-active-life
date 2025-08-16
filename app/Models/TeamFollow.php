<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TeamFollow extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'follower_id', 'id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
