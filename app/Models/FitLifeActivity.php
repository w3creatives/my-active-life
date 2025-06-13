<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FitLifeActivity extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeAvailable($query)
    {

        $currentDate = Carbon::now()->format('Y-m-d');

        return $query->where('available_from', '<=', $currentDate)->where('available_until', '>=', $currentDate);
    }

    public function getDescriptionAttribute()
    {
        $data = $this->attributes['description'] ? json_decode($this->attributes['description'], true) : $this->attributes['description'];

        return $data['description'] ?? $this->attributes['description'];
    }

    public function getAboutTitleAttribute()
    {
        $data = $this->attributes['description'] ? json_decode($this->attributes['description'], true) : null;

        return $data['about_title'] ?? null;
    }

    public function getAboutDescriptionAttribute()
    {
        $data = $this->attributes['description'] ? json_decode($this->attributes['description'], true) : null;

        return $data['about_description'] ?? null;
    }

    public function getPrizeUrlAttribute()
    {
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['prize']['url'] ?? null;
    }

    public function getPrizeDescriptionAttribute()
    {
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['prize']['description'] ?? null;
    }

    public function registrations()
    {
        return $this->hasMany(FitLifeActivityRegistration::class, 'activity_id', 'id');
    }

    public function invitations()
    {
        return $this->hasMany(FitLifeInvitation::class, 'activity_id', 'id');
    }

    public function milestones()
    {
        return $this->hasMany(FitLifeActivityMilestone::class, 'activity_id', 'id');
    }
}
