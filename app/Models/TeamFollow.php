<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamFollow extends Model
{
    protected $guarded = [];
    
    public function user(){
        return $this->belongsTo(User::class,'follower_id','id');
    }
    
     public function team(){
        return $this->belongsTo(Team::class);
    }
    
    public function event(){
        return $this->belongsTo(Event::class);
    }
}
