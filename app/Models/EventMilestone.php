<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventMilestone extends Model
{
    use HasFactory;
    
    public function getLogoAttribute(){
        return url("static/milestones/amerithon-calendar-1.png");
    }
    
    public function getTeamLogoAttribute(){ 
        return url("static/milestones/amerithon-calendar-1.png");
    }
}
