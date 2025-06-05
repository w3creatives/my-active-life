<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitLifeActivityMilestone extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getVideoUrlAttribute(){
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['flyover_url'] ?? null;
    }
}
