<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventMilestone extends Model
{
    use HasFactory;

    protected $fillable = ['name','distance','data','description','logo','team_log'];

    public function getLogoAttribute($value){
        if(!$value){
            return null;
        }
        return url("uploads/milestones/".$value);
    }

    public function getTeamLogoAttribute($value){
        if(!$value){
            return null;
        }
        return url("uploads/milestones/".$value);
    }

    public function getVideoUrlAttribute(){
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['flyover_url'] ?? null;
    }
}
