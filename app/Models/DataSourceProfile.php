<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSourceProfile extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    public function source(){
        return $this->belongsTo(DataSource::class,'data_source_id','id');
    }
    
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
}
