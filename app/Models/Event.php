<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

final class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['logo_url'];

    private string $uploadPath = 'uploads/events/';

    public function getLogoUrlAttribute(): string|\Illuminate\Contracts\Routing\UrlGenerator|null
    {
        if (! isset($this->attributes['logo'])) {
            return null;
        }
        if (file_exists(public_path('static/'.$this->attributes['logo']))) {
            return url('static/'.trim($this->attributes['logo']));
        }

        $fileurl = $this->uploadPath.trim($this->attributes['logo']);

        return Storage::url($fileurl);
    }

    public function isPastEvent(): bool
    {
        return Carbon::parse($this->end_date)->isPast();
    }

    public function scopeType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopePromotional($query)
    {
        return $query->where('event_type', 'promotional');
    }

    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', date('Y-m-d'));
    }

    public function scopeInactive($query)
    {
        return $query->where('end_date', '<', Carbon::now());
    }

    public function scopeAllowedTypes($query)
    {
        return $query->whereIn('event_type', ['fit_life', 'regular', 'promotional', 'month']);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(EventMilestone::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function fitActivities(): HasMany
    {
        return $this->hasMany(FitLifeActivity::class);
    }

    public function streaks(): HasMany
    {
        return $this->hasMany(EventStreak::class);
    }

    public function fitLifeRegistrations(): HasManyThrough
    {
        return $this->hasManyThrough(FitLifeActivityRegistration::class, FitLifeActivity::class, 'event_id', 'activity_id');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id', 'id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(EventParticipation::class, 'event_id', 'id');
    }

    public function tutorials(): HasMany
    {
        return $this->hasMany(EventTutorial::class, 'event_id', 'id');
    }

    public function hasUserParticipation($user, $count = true, $field = null)
    {

        if (! $user) {
            return false;
        }

        $participation = $this->participations()->where('user_id', $user->id);

        if ($count) {
            return $participation->count();
        }

        $participation = $participation->first();

        if (! $participation) {
            return false;
        }

        return $participation->$field;
    }
}
