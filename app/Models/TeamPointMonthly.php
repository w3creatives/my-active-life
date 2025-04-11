<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPointMonthly extends Model
{
    use HasFactory;
    
    protected $table = "team_points_monthlies";
    
     protected $guarded = [];
    
    public function event(){
        return $this->belongsTo(Event::class);
    }
    
    public function scopeHasEvent($query, $eventId){
     
        return $query->where('event_id', $eventId);
    }

}
