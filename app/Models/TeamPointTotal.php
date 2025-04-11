<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPointTotal extends Model
{
    use HasFactory;
    
    protected $table = 'team_points_totals';
    
    protected $guarded = [];
    
    public function scopeHasEvent($query, $eventId){
         return $query->where('event_id', $eventId);
     }
}
