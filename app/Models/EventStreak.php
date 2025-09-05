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

    private string $bibUploadPath = 'uploads/streaks/bibs/';

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

    public function getCalendarLogoAttribute(): ?string
    {

        if (! $this->attributes['calendar_logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['calendar_logo'])));
    }

    public function getBibImageAttribute(): ?string
    {

        if (! $this->attributes['bib_image']) {
            return null;
        }

        return asset(Storage::url($this->bibUploadPath.trim($this->attributes['bib_image'])));
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id', 'id');
    }

    public function images(): array
    {
        return [
            'logo_image_url' => $this->logo,
            'team_logo_image_url' => null,
            'calendar_logo_image_url' => $this->calendar_logo,
            'calendar_team_logo_image_url' => null,
        ];
    }
}
