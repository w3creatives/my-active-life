<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
#use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'encrypted_password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'encrypted_password' => 'hashed',
    ];
    
    public function getAuthPassword() {
        return $this->encrypted_password;
    }
    
    public function memberships(){
        return $this->hasMany(TeamMembership::class);
    }
    
    public function points(){
        return $this->hasMany(UserPoint::class);
    }
    
    public function participations(){
        return $this->hasMany(EventParticipation::class);
    }
    
    public function achievements(){
        return $this->hasMany(UserAchievement::class);
    }
     
    public function monthlyPoints(){
        return $this->hasMany(PointMonthly::class);
    }
    public function weeklyPoints(){
        return $this->hasMany(PointWeekly::class);
    }
    
    public function totalPoints(){
        return $this->hasMany(PointTotal::class);
    }
    
    public function profiles(){
        return $this->hasMany(DataSourceProfile::class);
    }
    
    public function invites(){
        return $this->hasMany(TeamMembershipInvite::class, 'prospective_member_id','id');
    }
    
    public function requests(){
        return $this->hasMany(TeamMembershipRequest::class, 'prospective_member_id','id');
    }
    
    public function teams(){
        return $this->hasMany(Team::class, 'owner_id','id');
    }
    
    public function teamFollowings(){
        return $this->hasMany(TeamFollow::class,'follower_id','id');
    }
     
    public function teamFollowingRequests(){
        return $this->hasMany(TeamFollowRequest::class,'prospective_follower_id','id');
    }
    
    public function questRegistrations(){
        return $this->hasMany(FitLifeActivityRegistration::class);
    }
    
    public function questMilestones(){
        return $this->hasMany(FitLifeActivityMilestone::class);
    }
    
    public function profilePoints(){
        return $this->hasMany(UserProfilePoint::class);
    }
    
    public function following(){
        return $this->hasMany(UserFollow::class,'follower_id','id');
    }
    
    public function followers(){
        return $this->hasMany(UserFollow::class,'followed_id','id');
    }
    
    public function followRequests(){
        return $this->hasMany(UserFollowRequest::class,'followed_id','id');
    }
    
    public function followingRequests(){
        return $this->hasMany(UserFollowRequest::class,'prospective_follower_id','id');
    }
}
