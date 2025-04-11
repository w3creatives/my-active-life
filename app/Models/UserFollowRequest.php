<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollowRequest extends Model
{
    protected $guarded = [];
    
    public function follower(){
        return $this->belongsTo(User::class,'prospective_follower_id','id');
    }
    
    public function following(){
        return $this->belongsTo(User::class,'followed_id','id');
    }
    
}
