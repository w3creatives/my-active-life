<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UserFollow extends Model
{
    protected $guarded = [];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id', 'id');
    }

    public function following()
    {
        return $this->belongsTo(User::class, 'followed_id', 'id');
    }
}
