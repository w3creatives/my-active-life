<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamAchievement extends Model
{
       protected $guarded = [];
       
        public function scopeHasEvent($query, $eventId){
         
            return $query->where('event_id', $eventId);
        }
}
