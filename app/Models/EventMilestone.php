<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class EventMilestone extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'distance', 'data', 'description', 'logo', 'team_logo', 'calendar_logo', 'calendar_team_logo', 'email_template_id', 'bib_image', 'team_bib_image'];

    private string $uploadPath = 'uploads/milestones/';

    private string $bibUploadPath = 'uploads/milestones/bibs/';

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

    public function getCalendarLogoAttribute(): ?string
    {

        $data = $this->attributes['calendar_logo']??null;
        if(!$data){
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['calendar_logo'])));
    }

    public function getCalendarTeamLogoAttribute(): ?string
    {

        if (! $this->attributes['calendar_team_logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['calendar_team_logo'])));
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

    public function getVideoUrlAttribute()
    {
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['flyover_url'] ?? null;
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id', 'id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function images(): array
    {
        return [
            'logo_image_url' => $this->logo,
            'team_logo_image_url' => $this->team_logo,
            'calendar_logo_image_url' => $this->calendar_logo,
            'calendar_team_logo_image_url' => $this->calendar_team_logo,
        ];
    }
}
