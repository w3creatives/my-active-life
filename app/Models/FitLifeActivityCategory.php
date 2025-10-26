<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class FitLifeActivityCategory extends Model
{
    protected $guarded = [];

    private string $uploadPath = 'uploads/fitlife-categories/';

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->attributes['logo']) {
            return null;
        }

        return asset(Storage::url($this->uploadPath.trim($this->attributes['logo'])));
    }
}
