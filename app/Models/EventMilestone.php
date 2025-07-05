<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class EventMilestone extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'distance', 'data', 'description', 'logo', 'team_log'];

    private string $uploadPath = 'uploads/milestones/';

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

    public function getVideoUrlAttribute()
    {
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['flyover_url'] ?? null;
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id', 'id');
    }
}
