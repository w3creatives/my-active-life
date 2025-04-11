<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitLifeActivityRegistration extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    public function activity(){
        return $this->belongsTo(FitLifeActivity::class,'activity_id','id');
    }
    
    public function milestoneStatuses(){
        return $this->hasMany(FitLifeActivityMilestoneStatus::class,'registration_id','id');
    }
}
