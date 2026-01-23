<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Modality extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    public function events()
    {
        return $this->belongsToMany(Event::class);
    }
}
