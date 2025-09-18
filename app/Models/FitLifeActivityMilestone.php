<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

final class FitLifeActivityMilestone extends Model
{
    use HasFactory;

    protected $guarded = [];

    private string $uploadPath = 'uploads/milestones/';
    private string $bibUploadPath = 'uploads/milestones/bibs/';

    public function activity(): BelongsTo
    {
        return $this->belongsTo(FitLifeActivity::class,'activity_id','id');
    }

    public function getVideoUrlAttribute()
    {
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['flyover_url'] ?? null;
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

    public function getBwLogoAttribute(): ?string
    {

        if (! $this->attributes['bw_logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['bw_logo'])));
    }

    public function getBwCalendarLogoAttribute(): ?string
    {

        if (! $this->attributes['bw_calendar_logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['bw_calendar_logo'])));
    }

    public function getBibImageAttribute(): ?string
    {

        if (! $this->attributes['bib_image']) {
            return null;
        }

        return asset(Storage::url($this->bibUploadPath.trim($this->attributes['bib_image'])));
    }

    public function getTeamBibImageAttribute(): ?string
    {

        if (! $this->attributes['team_bib_image']) {
            return null;
        }

        return asset(Storage::url($this->bibUploadPath.trim($this->attributes['team_bib_image'])));
    }

    public function images($isCompleted): array
    {
        return [
            'logo_image_url' => $isCompleted ? $this->logo : $this->bw_logo,
            'team_logo_image_url' => null,
            'calendar_logo_image_url' => $isCompleted ? $this->calendar_logo : $this->bw_calendar_logo,
            'calendar_team_logo_image_url' => null,
        ];
    }
}
