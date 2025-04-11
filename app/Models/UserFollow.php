<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    protected $guarded = [];
    
    public function follower(){
        return $this->belongsTo(User::class,'follower_id','id');
    }
    
    public function following(){
        return $this->belongsTo(User::class,'followed_id','id');
    }
 
}
