<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamFollowRequest extends Model
{
    protected $guarded = [];
    
    public function user(){
        return $this->belongsTo(User::class,'prospective_follower_id','id');
    }
    
    public function team(){
        return $this->belongsTo(Team::class);
    }
}
