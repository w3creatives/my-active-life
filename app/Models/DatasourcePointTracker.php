<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatasourcePointTracker extends Model
{
    protected $guarded = [];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Datasource::class, 'datasource_id', 'id');
    }
}
