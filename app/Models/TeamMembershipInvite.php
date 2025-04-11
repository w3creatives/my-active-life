<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMembershipInvite extends Model
{
    protected $guarded = [];
    
    public function user(){
        return $this->belongsTo(User::class,'prospective_member_id','id');
    }
    
    public function team(){
        return $this->belongsTo(Team::class,'team_id','id');
    }
    
    public function event(){
        return $this->belongsTo(Event::class,'event_id','id');
    }
}
