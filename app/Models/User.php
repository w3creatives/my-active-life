<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Passport\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Impersonate, Notifiable;

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

    public function getFullNameAttribute()
    {
        return implode(' ', [$this->attributes['first_name'], $this->attributes['last_name']]);
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

    public function profileLogs(): HasMany
    {
        return $this->hasMany(DataSourceProfileLog::class);
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

    public function displayedMilestones(): HasMany
    {
        return $this->hasMany(DisplayedUserMilestone::class, 'user_id', 'id');
    }

    public function userStreaks(): HasMany
    {
        return $this->hasMany(UserStreak::class, 'user_id', 'id');
    }

    public function displayedStreaks(): HasMany
    {
        return $this->hasMany(DisplayedUserStreak::class);
    }

    public function preferredEvent(): HasOne
    {
        return $this->hasOne(Event::class, 'id', 'preferred_event_id');
    }

    public function fitLifeRegistrations(): HasMany
    {
        return $this->hasMany(FitLifeActivityRegistration::class, 'user_id', 'id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ClientUser::class, 'user_id', 'id');
    }

    /**
     * Check if the user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->super_admin;
    }

    public function logSourceConnected($data): void
    {
        $data['action'] = $data['action'] ?? 'CONNECTED';
        $this->profileLogs()->create($data);
    }

    public function logSourceDisconnected($data): void
    {
        $data['action'] = $data['action'] ?? 'DISCONNECTED';
        $this->profileLogs()->create($data);
    }

    /**
     * Retrieves MailboxerConversation instances associated with the current user.
     *
     * Filters conversations where notifications are sent by the current user and loads related notification receipts.
     */
    public function mailboxerConversations()
    {
        return MailboxerConversation::whereHas('notifications', function ($query) {
            $query->where('sender_id', $this->id);
        })
            ->with([
                'notifications' => function ($query) {
                    $query->with('receipts');
                },
            ]);
    }

    /**
     * Retrieves inbox messages for the current user.
     *
     * Filters MailboxerReceipt instances where the user is the receiver and the mailbox type is 'inbox'.
     * Loads related notification data including conversations and sender details.
     */
    public function inboxMessages()
    {
        return MailboxerReceipt::where('receiver_id', $this->id)
            ->where('mailbox_type', 'inbox')
            ->with([
                'notification.conversation',
                'notification.sender',
            ]);
    }

    /**
     * Retrieves sent messages for the current user.
     *
     * Filters receipts where the receiver is the current user and the mailbox type is 'sentbox'.
     * Loads related notification conversation and sender details.
     */
    public function sentMessages()
    {
        return MailboxerReceipt::where('receiver_id', $this->id)
            ->where('mailbox_type', 'sentbox')
            ->with([
                'notification.conversation',
                'notification.sender',
            ]);
    }

    public function hasClient($client): bool
    {
        return $this->clients()->where('client_id', $client->id)->exists();
    }

    public function hasPoint($eventId): bool
    {
        return $this->points()->where('event_id', $eventId)->exists();
    }
}
