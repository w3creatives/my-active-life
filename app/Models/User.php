<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Impersonate;

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

    protected $authPasswordName = 'encrypted_password';

    protected $appends = ['time_zone_name'];

    public function getTimeZoneNameAttribute()
    {
        if (! $this->time_zone) {
            return null;
        }

        $timezones = config('timezones.timezone');
        $zones = config('timezones.zones');

        preg_match('#\((.*?)\)#', $timezones[$this->time_zone], $match);

        return $zones[$match[1]];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(UserPoint::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(EventParticipation::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function monthlyPoints(): HasMany
    {
        return $this->hasMany(PointMonthly::class);
    }

    public function weeklyPoints(): HasMany
    {
        return $this->hasMany(PointWeekly::class);
    }

    public function totalPoints(): HasMany
    {
        return $this->hasMany(PointTotal::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(DataSourceProfile::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamMembershipInvite::class, 'prospective_member_id', 'id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(TeamMembershipRequest::class, 'prospective_member_id', 'id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id', 'id');
    }

    public function teamFollowings(): HasMany
    {
        return $this->hasMany(TeamFollow::class, 'follower_id', 'id');
    }

    public function teamFollowingRequests(): HasMany
    {
        return $this->hasMany(TeamFollowRequest::class, 'prospective_follower_id', 'id');
    }

    public function questRegistrations(): HasMany
    {
        return $this->hasMany(FitLifeActivityRegistration::class);
    }

    public function questMilestones(): HasMany
    {
        return $this->hasMany(FitLifeActivityMilestone::class);
    }

    public function profilePoints(): HasMany
    {
        return $this->hasMany(UserProfilePoint::class);
    }

    public function following(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'follower_id', 'id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'followed_id', 'id');
    }

    public function followRequests(): HasMany
    {
        return $this->hasMany(UserFollowRequest::class, 'followed_id', 'id');
    }

    public function followingRequests(): HasMany
    {
        return $this->hasMany(UserFollowRequest::class, 'prospective_follower_id', 'id');
    }

    /**
     * Check if the user is a super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->super_admin;
    }
}
