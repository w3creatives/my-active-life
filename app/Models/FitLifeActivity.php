<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class FitLifeActivity extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeAvailable($query){


        $currentDate = Carbon::now()->format('Y-m-d');

        return $query->where('available_from','<=', $currentDate)->where('available_until','>=',$currentDate);
    }

    public function registrations(){
        return $this->hasMany(FitLifeActivityRegistration::class,'activity_id','id');
    }

    public function invitations(){
        return $this->hasMany(FitLifeInvitation::class,'activity_id','id');
    }

    public function milestones(){
        return $this->hasMany(FitLifeActivityMilestone::class,'activity_id','id');
    }
}
