<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointWeekly extends Model
{
    use HasFactory;
    
    protected $table = 'points_weeklies';
    
    protected $guarded = [];
    
    public function scopeHasEvent($query, $eventId){
         return $query->where('event_id', $eventId);
     }
}
