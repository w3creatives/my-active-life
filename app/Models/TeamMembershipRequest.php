<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMembershipRequest extends Model
{
    protected $guarded = [];
    
    public function user(){
        return $this->belongsTo(User::class,'prospective_member_id','id');
    }
}
