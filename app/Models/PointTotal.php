<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PointTotal extends Model
{
    use HasFactory;

    protected $table = 'points_totals';

    protected $guarded = [];

    public function scopeHasEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
