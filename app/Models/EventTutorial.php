<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

final class EventTutorial extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    public function getContentAttribute(): Collection
    {
        $content = [];

        if (isset($this->attributes['tutorial_text'])) {
            $content = json_decode($this->attributes['tutorial_text'], false);
        }

        return collect($content);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
