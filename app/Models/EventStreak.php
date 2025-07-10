<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

final class EventStreak extends Model
{
    protected $guarded = [];

    private string $uploadPath = 'uploads/streaks/';

    public function getMinDistanceAttribute()
    {
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['min_distance'] ?? null;
    }

    public function getLogoAttribute(): ?string
    {

        if (! $this->attributes['logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['logo'])));
    }

    public function getTeamLogoAttribute(): ?string
    {

        if (! $this->attributes['team_logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['team_logo'])));
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id', 'id');
    }
}
