<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    
     protected $guarded = [];
     
     public function event(){
         return $this->belongsTo(Event::class);
     }
     
     public function scopeHasEvent($query, $eventId){
         
         return $query->where('event_id', $eventId);
     }
}

