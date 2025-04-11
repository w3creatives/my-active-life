<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    
    protected $guarded = [];
    
    public function memberships(){
        return $this->hasMany(TeamMembership::class);
    }
    
    public function owner(){
        return $this->belongsTo(User::class,'owner_id');
    }
    
    public function event(){
        return $this->belongsTo(Event::class);
    }
    
     public function achievements(){
        return $this->hasMany(TeamAchievement::class);
    }
    
    public function monthlyPoints(){
        return $this->hasMany(TeamPointMonthly::class);
    }
    
    public function weeklyPoints(){
        return $this->hasMany(TeamPointWeekly::class);
    }
    
    public function totalPoints(){
        return $this->hasMany(TeamPointTotal::class);
    }
    
    public function invites(){
        return $this->hasMany(TeamMembershipInvite::class);
    }
    
    public function followerRequests(){
        return $this->hasMany(TeamFollowRequest::class);
    }
    
    public function followers(){
        return $this->hasMany(TeamFollow::class);
    }
    
    public function requests(){
        return $this->hasMany(TeamMembershipRequest::class);
    }
    
    public function points(){
        return $this->hasMany(TeamPoint::class);
    }
}
