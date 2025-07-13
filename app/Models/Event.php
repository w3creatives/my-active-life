<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @method static promotional()
 */
class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['logo_url'];

    private string $uploadPath = 'uploads/events/';

    public function getLogoUrlAttribute()
    {
        if(!isset($this->attributes['logo'])) {
            return null;
        }
        if(file_exists(public_path('static/' . $this->attributes['logo']))){
            return url("static/" . trim($this->attributes['logo']));
        }

        $fileurl = $this->uploadPath.trim($this->attributes['logo']);
        return Storage::url($fileurl);
    }

    public function isPastEvent()
    {
        return Carbon::parse($this->end_date)->isPast();
    }

    public function scopeType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopePromotional($query){
        return $query->where('event_type', 'promotional');
    }

    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', date('Y-m-d'));
    }
    public  function scopeAllowedTypes($query)
    {
        return $query->whereIn('event_type', ['fit_life','regular','promotional','month']);
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function milestones()
    {
        return $this->hasMany(EventMilestone::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function fitActivities()
    {
        return $this->hasMany(FitLifeActivity::class);
    }

    public function streaks()
    {
        return $this->hasMany(EventStreak::class);
    }

    public function fitLifeRegistrations()
    {
        return $this->hasManyThrough(FitLifeActivityRegistration::class, FitLifeActivity::class,'event_id','activity_id');
    }

    public function emailTemplate(){
        return $this->belongsTo(EmailTemplate::class,'email_template_id','id');
    }
    public function participations()
    {
        return $this->hasMany(EventParticipation::class,'event_id','id');
    }

    public function tutorials()
    {
        return $this->hasMany(EventTutorial::class,'event_id','id');
    }

    public function hasUserParticipation($user, $count = true, $field=null){

        if(!$user) {
            return false;
        }

        $participation = $this->participations()->where('user_id',$user->id);

        if($count){
            return $participation->count();
        }

        $participation = $participation->first();

        if(!$participation){
            return false;
        }

        return $participation->$field;
    }
}
