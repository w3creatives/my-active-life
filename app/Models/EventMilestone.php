<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class EventMilestone extends Model
{
    use HasFactory;

    protected $fillable = ['name','distance','data','description','logo','team_log'];

    private string $uploadPath = 'uploads/milestones/';

    public function getLogoAttribute(){

        if(!trim($this->attributes['logo'])){
            return null;
        }

        return Storage::url($this->uploadPath.trim($this->attributes['logo']));
    }

    public function getTeamLogoAttribute(){

        if(!trim($this->attributes['team_logo'])){
            return null;
        }
        return Storage::url($this->uploadPath.trim($this->attributes['team_logo']));
    }

    public function getVideoUrlAttribute(){
        $data = $this->data ? json_decode($this->data, true) : null;

        return $data['flyover_url'] ?? null;
    }
}
