<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FitLifeActivityMilestone extends Model
{
    use HasFactory;

    protected $guarded = [];

    private string $uploadPath = 'uploads/milestones/';

    public function getVideoUrlAttribute(){
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['flyover_url'] ?? null;
    }

    public function getLogoAttribute()
    {

        if (! $this->attributes['logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['logo'])));
    }

    public function getTeamLogoAttribute()
    {

        if (! $this->attributes['team_logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['team_logo'])));
    }
}
