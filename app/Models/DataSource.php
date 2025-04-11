<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    use HasFactory;

    public static function authUrls($key = null){
        $authUrls = [
            'fitbit' => route('fitbit.oauth','app'),
            'strava' => route('strava.oauth'),
            'garmin' => route('garmin.oauth', 'app')
        ];
        
        if(!$key) {
            return $authUrls;
        }
        
        if(isset($authUrls[$key])){
            return $authUrls[$key];
        }
        
        return null;
    }
    
    protected $appends = ['image_url']; 
    
    public function getImageUrlAttribute()
    {
        if($this->short_name == 'manual'){
            return null;
        }
        return url(sprintf("static/sources/%s.png",$this->short_name));
    
    }
    
      public function getOauthUrl22Attribute()
    {
        
        return static::authUrls($this->short_name);
    
    }
    
    public function sourceProfile(){
        return $this->hasOne(DataSourceProfile::class,'data_source_id','id');
    }
}
