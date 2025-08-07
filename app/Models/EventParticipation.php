<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EventParticipation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected $appends = ['modality_overrides'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isModalityOverridden($modality): bool
    {
        $participationSetting = json_decode($this->settings, true);

        $modalityOverrides = $participationSetting['modality_overrides'] ?? ['daily_steps', 'run', 'walk'];

        $modalityOverrides[] = 'other';

        return in_array($modality, $modalityOverrides);
    }

    public function getModalityOverridesAttribute(): array
    {
        $participationSetting = json_decode($this->settings, true);

        $modalityOverrides = $participationSetting['modality_overrides'] ?? ['daily_steps', 'run', 'walk'];

        $modalityOverrides[] = 'other';

        return $modalityOverrides;
    }
}
