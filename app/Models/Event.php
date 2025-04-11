<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{ 
    use HasFactory;
    
    protected $appends = ['logo_url'];
    
    public function getLogoUrlAttribute(){
        return url("static/".trim($this->logo));
    }
    
    public function organization(){
        return $this->belongsTo(Organization::class);
    }
    
    public function milestones(){
        return $this->hasMany(EventMilestone::class);
    }
    
    public function teams(){
        return $this->hasMany(Team::class);
    }
    
    public function fitActivities(){
        return $this->hasMany(FitLifeActivity::class);
    }
    
    public function participations(){
        return $this->hasMany(EventParticipation::class);
    }
}
