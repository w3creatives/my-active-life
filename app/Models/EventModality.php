<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventModality extends Model
{
    use HasFactory;

	protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }
}